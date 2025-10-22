<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Project Workflow Panel</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/project-panel.css', 'resources/js/project-panel.js'])
    </head>
    <body class="project-body">
        <header class="project-header">
            <div class="project-header__content">
                <h1 class="project-title">Project Workflow Designer</h1>
                <p class="project-subtitle">
                    Connect project stages to the teammates responsible for them and let the panel generate the supporting tasks automatically.
                </p>
            </div>
            <button id="seed-workflow" class="project-button project-button--secondary" type="button">
                Load example workflow
            </button>
        </header>

        <main class="project-layout">
            <aside class="project-sidebar">
                <section class="project-section">
                    <h2 class="project-section__title">Stage library</h2>
                    <form id="stage-form" class="project-form">
                        <label class="project-label" for="stage-name">Stage name</label>
                        <input id="stage-name" name="stage-name" class="project-input" type="text" placeholder="e.g. Planning" required>

                        <label class="project-label" for="stage-objective">Objective</label>
                        <input id="stage-objective" name="stage-objective" class="project-input" type="text" placeholder="Key deliverable">

                        <label class="project-label" for="stage-duration">Target duration (days)</label>
                        <input id="stage-duration" name="stage-duration" class="project-input" type="number" min="0" step="1" placeholder="5">

                        <button class="project-button" type="submit">Add stage node</button>
                    </form>
                </section>

                <section class="project-section">
                    <h2 class="project-section__title">Team members</h2>
                    <form id="user-form" class="project-form">
                        <label class="project-label" for="user-name">Name</label>
                        <input id="user-name" name="user-name" class="project-input" type="text" placeholder="e.g. Taylor" required>

                        <label class="project-label" for="user-role">Role</label>
                        <input id="user-role" name="user-role" class="project-input" type="text" placeholder="Product Manager">

                        <label class="project-label" for="user-capacity">Weekly capacity (hours)</label>
                        <input id="user-capacity" name="user-capacity" class="project-input" type="number" min="0" step="1" placeholder="40">

                        <button class="project-button" type="submit">Add teammate node</button>
                    </form>
                </section>

                <section class="project-section project-section--compact">
                    <h2 class="project-section__title">Workflow summary</h2>
                    <dl class="project-stats">
                        <div class="project-stats__item">
                            <dt>Stages</dt>
                            <dd id="stat-stages">0</dd>
                        </div>
                        <div class="project-stats__item">
                            <dt>Teammates</dt>
                            <dd id="stat-users">0</dd>
                        </div>
                        <div class="project-stats__item">
                            <dt>Assignments</dt>
                            <dd id="stat-assignments">0</dd>
                        </div>
                    </dl>
                </section>
            </aside>

            <section class="project-canvas">
                <div id="project-workflow-editor" class="project-editor" role="application" aria-label="Workflow designer canvas"></div>
            </section>
        </main>

        <section class="project-tasks">
            <div class="project-tasks__header">
                <h2>Auto-generated task list</h2>
                <p>Connect a stage to a teammate to see what needs to happen next. Tasks update instantly as you edit node information.</p>
            </div>
            <ul id="workflow-tasks" class="project-task-groups" aria-live="polite">
                <li class="project-task-group project-task-group--empty">
                    <span>No assignments yet. Create a connection between a stage and a teammate to generate tasks.</span>
                </li>
            </ul>
        </section>

        <template id="task-group-template">
            <li class="project-task-group">
                <header class="project-task-group__header">
                    <h3 class="project-task-group__title"></h3>
                    <span class="project-task-group__meta"></span>
                </header>
                <ul class="project-task-items"></ul>
            </li>
        </template>
    </body>
</html>
