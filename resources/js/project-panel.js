import { createRoot } from 'react-dom/client';
import { ClassicPreset, NodeEditor } from 'rete';
import { AreaPlugin, AreaExtensions } from 'rete-area-plugin';
import { ConnectionPlugin, Presets as ConnectionPresets } from 'rete-connection-plugin';
import { ReactPlugin, Presets as ReactPresets } from 'rete-react-plugin';

const container = document.getElementById('project-workflow-editor');

if (container) {
    initProjectPanel(container).catch((error) => {
        console.error('Failed to boot the project workflow editor', error);
    });
}

async function initProjectPanel(container) {
    const editor = new NodeEditor();
    const area = new AreaPlugin(container);
    const render = new ReactPlugin({ createRoot });
    render.addPreset(ReactPresets.classic.setup());

    editor.use(area);
    area.use(render);

    const connection = new ConnectionPlugin();
    connection.addPreset(ConnectionPresets.classic.setup());
    area.use(connection);

    const stageForm = document.getElementById('stage-form');
    const userForm = document.getElementById('user-form');
    const tasksList = document.getElementById('workflow-tasks');
    const template = document.getElementById('task-group-template');
    const seedButton = document.getElementById('seed-workflow');
    const statStages = document.getElementById('stat-stages');
    const statUsers = document.getElementById('stat-users');
    const statAssignments = document.getElementById('stat-assignments');

    const workflowSocket = new ClassicPreset.Socket('Workflow');

    class StageNode extends ClassicPreset.Node {
        constructor(stage) {
            super(stage.name || 'Stage');
            this.data = { type: 'stage', stage };

            this.addControl(
                'objective',
                new ClassicPreset.InputControl('text', {
                    initial: stage.objective || '',
                    placeholder: 'Key objective',
                    change: (value) => {
                        this.data.stage.objective = value;
                        scheduleRefresh();
                    },
                })
            );

            this.addControl(
                'duration',
                new ClassicPreset.InputControl('number', {
                    initial: stage.duration ?? '',
                    change: (value) => {
                        const parsed = Number(value);
                        this.data.stage.duration = Number.isFinite(parsed) ? parsed : 0;
                        scheduleRefresh();
                    },
                })
            );

            this.addOutput('assignment', new ClassicPreset.Output(workflowSocket, 'Assign to'));
            this.addOutput('handoff', new ClassicPreset.Output(workflowSocket, 'Handoff'));
        }
    }

    class UserNode extends ClassicPreset.Node {
        constructor(user) {
            super(user.name || 'Teammate');
            this.data = { type: 'user', user };

            this.addControl(
                'role',
                new ClassicPreset.InputControl('text', {
                    initial: user.role || '',
                    placeholder: 'Role or focus area',
                    change: (value) => {
                        this.data.user.role = value;
                        scheduleRefresh();
                    },
                })
            );

            this.addControl(
                'capacity',
                new ClassicPreset.InputControl('number', {
                    initial: user.capacity ?? '',
                    change: (value) => {
                        const parsed = Number(value);
                        this.data.user.capacity = Number.isFinite(parsed) ? parsed : 0;
                        scheduleRefresh();
                    },
                })
            );

            this.addInput('incoming', new ClassicPreset.Input(workflowSocket, 'Stage', true));
            this.addOutput('handoff', new ClassicPreset.Output(workflowSocket, 'Handoff'));
        }
    }

    const scheduleRefresh = debounce(refreshWorkflowSummary, 80);

    editor.addPipe((context) => {
        if (
            context.type === 'nodecreated' ||
            context.type === 'noderemoved' ||
            context.type === 'connectioncreated' ||
            context.type === 'connectionremoved'
        ) {
            scheduleRefresh();
        }

        return context;
    });

    stageForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(stageForm);
        const stage = {
            name: formData.get('stage-name')?.toString().trim() || 'Stage',
            objective: formData.get('stage-objective')?.toString().trim() || '',
            duration: parseInt(formData.get('stage-duration'), 10),
        };

        if (!Number.isFinite(stage.duration)) {
            stage.duration = undefined;
        }

        const node = new StageNode(stage);
        await editor.addNode(node);
        await placeStageNode(node, area, editor);

        stageForm.reset();
        scheduleRefresh();
    });

    userForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(userForm);
        const user = {
            name: formData.get('user-name')?.toString().trim() || 'Teammate',
            role: formData.get('user-role')?.toString().trim() || '',
            capacity: parseInt(formData.get('user-capacity'), 10),
        };

        if (!Number.isFinite(user.capacity)) {
            user.capacity = undefined;
        }

        const node = new UserNode(user);
        await editor.addNode(node);
        await placeUserNode(node, area, editor);

        userForm.reset();
        scheduleRefresh();
    });

    seedButton?.addEventListener('click', async () => {
        if (editor.getNodes().length > 0) {
            AreaExtensions.zoomAt(area, editor.getNodes());
            return;
        }

        const discovery = new StageNode({
            name: 'Discovery',
            objective: 'Clarify goals and constraints',
            duration: 5,
        });
        const design = new StageNode({
            name: 'Design',
            objective: 'Produce wireframes and prototypes',
            duration: 7,
        });
        const delivery = new StageNode({
            name: 'Delivery',
            objective: 'Coordinate launch readiness',
            duration: 10,
        });

        const lead = new UserNode({ name: 'Jordan Blake', role: 'Project Lead', capacity: 35 });
        const designer = new UserNode({ name: 'Morgan Lee', role: 'UX Designer', capacity: 32 });
        const ops = new UserNode({ name: 'Riley Chen', role: 'Operations', capacity: 28 });

        await editor.addNode(discovery);
        await editor.addNode(design);
        await editor.addNode(delivery);
        await editor.addNode(lead);
        await editor.addNode(designer);
        await editor.addNode(ops);

        await placeStageNode(discovery, area, editor);
        await placeStageNode(design, area, editor);
        await placeStageNode(delivery, area, editor);
        await placeUserNode(lead, area, editor);
        await placeUserNode(designer, area, editor);
        await placeUserNode(ops, area, editor);

        await editor.addConnection(new ClassicPreset.Connection(discovery, 'assignment', lead, 'incoming'));
        await editor.addConnection(new ClassicPreset.Connection(design, 'assignment', designer, 'incoming'));
        await editor.addConnection(new ClassicPreset.Connection(delivery, 'assignment', ops, 'incoming'));
        await editor.addConnection(new ClassicPreset.Connection(lead, 'handoff', designer, 'incoming'));
        await editor.addConnection(new ClassicPreset.Connection(designer, 'handoff', ops, 'incoming'));

        AreaExtensions.zoomAt(area, editor.getNodes());
        scheduleRefresh();
    });

    scheduleRefresh();

    function refreshWorkflowSummary() {
        const nodes = editor.getNodes();
        const stageNodes = nodes.filter((node) => node.data?.type === 'stage');
        const userNodes = nodes.filter((node) => node.data?.type === 'user');
        const assignments = [];

        editor.getConnections().forEach((connection) => {
            const sourceNode = resolveNode(editor, connection.source);
            const targetNode = resolveNode(editor, connection.target);

            if (sourceNode?.data?.type === 'stage' && targetNode?.data?.type === 'user') {
                assignments.push({ stageNode: sourceNode, userNode: targetNode });
            }
        });

        statStages.textContent = stageNodes.length.toString();
        statUsers.textContent = userNodes.length.toString();
        statAssignments.textContent = assignments.length.toString();

        renderAssignments(assignments, tasksList, template);
    }
}

function resolveNode(editor, reference) {
    if (!reference) {
        return null;
    }

    if (reference.node) {
        return reference.node;
    }

    if (reference.nodeId) {
        return editor.getNode(reference.nodeId);
    }

    if (typeof reference === 'string') {
        return editor.getNode(reference);
    }

    return null;
}

function renderAssignments(assignments, listElement, template) {
    if (!(listElement instanceof HTMLElement)) {
        return;
    }

    listElement.innerHTML = '';

    if (!assignments.length) {
        const empty = document.createElement('li');
        empty.className = 'project-task-group project-task-group--empty';
        empty.textContent = 'No assignments yet. Create a connection between a stage and a teammate to generate tasks.';
        listElement.append(empty);
        return;
    }

    assignments.forEach(({ stageNode, userNode }) => {
        const stage = stageNode.data.stage;
        const user = userNode.data.user;
        const clone = template?.content.firstElementChild?.cloneNode(true);

        const item = clone instanceof HTMLElement ? clone : document.createElement('li');
        item.classList.add('project-task-group');

        const title = item.querySelector('.project-task-group__title');
        const meta = item.querySelector('.project-task-group__meta');
        const tasks = item.querySelector('.project-task-items');

        const label = `${stage.name || 'Stage'} → ${user.name || 'Teammate'}`;
        const capacity = Number.isFinite(user.capacity) ? `${user.capacity}h capacity` : 'Capacity to confirm';
        const role = user.role ? `${user.role}` : 'Role not defined';

        if (title) {
            title.textContent = label;
        }

        if (meta) {
            meta.textContent = `${role} • ${capacity}`;
        }

        if (tasks) {
            tasks.innerHTML = '';
            buildTaskItems(stage, user).forEach((description) => {
                const li = document.createElement('li');
                li.textContent = description;
                tasks.append(li);
            });
        }

        listElement.append(item);
    });
}

function buildTaskItems(stage, user) {
    const objective = stage.objective || 'Clarify requirements';
    const duration = Number.isFinite(stage.duration) && stage.duration > 0 ? stage.duration : null;
    const capacity = Number.isFinite(user.capacity) && user.capacity > 0 ? user.capacity : null;

    return [
        `Kick-off: align ${user.name || 'the assignee'} on “${stage.name || 'current stage'}” goals (${objective}).`,
        `Execution: break down the objective into actionable tasks and prioritise with ${user.role || 'the assignee'}.`,
        duration
            ? `Timeline: plan for ${duration} day${duration === 1 ? '' : 's'} of work and schedule checkpoints.`
            : 'Timeline: define the expected duration and milestones for this stage.',
        capacity
            ? `Capacity check: ensure workload fits within ${capacity} hour${capacity === 1 ? '' : 's'} this week.`
            : 'Capacity check: confirm availability and redistribute if needed.',
        'Handoff: document outcomes and share status updates with the next connected node.',
    ];
}

async function placeStageNode(node, area, editor) {
    const stageNodes = editor.getNodes().filter((item) => item.data?.type === 'stage');
    const index = stageNodes.findIndex((item) => item.id === node.id);
    const column = index % 2;
    const row = Math.floor(index / 2);
    const x = 80 + column * 280;
    const y = 80 + row * 180;
    await area.translate(node.id, { x, y });
}

async function placeUserNode(node, area, editor) {
    const userNodes = editor.getNodes().filter((item) => item.data?.type === 'user');
    const index = userNodes.findIndex((item) => item.id === node.id);
    const column = index % 2;
    const row = Math.floor(index / 2);
    const x = 580 + column * 280;
    const y = 120 + row * 180;
    await area.translate(node.id, { x, y });
}

function debounce(callback, wait) {
    let frame = 0;
    return (...args) => {
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(() => {
            callback(...args);
        });
    };
}
