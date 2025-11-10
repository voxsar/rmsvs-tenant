# Verification Checklist for Changes and Confirmations

## Pre-Deployment Checklist

### Dashboard Verification
- [ ] Open dashboard and verify only 5 widgets are visible:
  - [ ] Persons On Site (with breakdown)
  - [ ] Emergency Roll Call List
  - [ ] Absences Longer Than 24 Hours
  - [ ] Missed Meals
  - [ ] Missed Consumables
- [ ] Verify NO charts are visible (no pie charts, no trend graphs)
- [ ] Verify NO "Welcome, Tenant Admin" box
- [ ] Verify NO "Last Shift Report" widget
- [ ] Verify NO "Absence History" section (different from "Absences Longer Than 24 Hours")

### Emergency Roll Call List
- [ ] Verify table shows these columns:
  - [ ] Resident name
  - [ ] Phone number
  - [ ] Email address
  - [ ] Room number
  - [ ] Last Seen timestamp
- [ ] Click on a resident with a photo - verify photo modal opens
- [ ] Verify modal shows:
  - [ ] Guest photo
  - [ ] Guest name
  - [ ] Guest email and phone

### Property Navigation
- [ ] Verify "Manual Check In" does NOT appear in navigation
- [ ] Verify "Guest Requests" does NOT appear in navigation
- [ ] Open "Room Assignments" (Check-Ins):
  - [ ] Verify you CAN view the list
  - [ ] Verify you CAN view individual assignments
  - [ ] Verify you CAN edit individual assignments
  - [ ] Verify you CANNOT access /admin/tenant.check-ins/create
  - [ ] Verify you CANNOT access /admin/tenant.check-ins/multi-guest
  - [ ] Select multiple assignments - verify NO "Generate QR Codes" bulk action

### Guest Profiles
- [ ] Open any guest profile (view page):
  - [ ] Verify "View QR Code" button in header
  - [ ] Verify "Regenerate QR Code" button in header
  - [ ] Click "View QR Code" - verify modal shows QR code image
  - [ ] Click "Regenerate QR Code" - verify confirmation dialog
  - [ ] Confirm regeneration - verify success notification
- [ ] Open any guest profile (edit page):
  - [ ] Verify same QR code buttons exist
  - [ ] Test both buttons work

### Scans Navigation
- [ ] Verify "Scan History" appears in navigation
- [ ] Verify "Scanners" appears in navigation
- [ ] Verify NO "Manual Scan" in navigation
- [ ] Open Scan History - verify it shows all activity records

### Settings Navigation
- [ ] Open "Scan Items":
  - [ ] Verify dropdown shows "Scan Type" (not "Item Type")
  - [ ] Verify three options: Access, Meals, Consumables
  - [ ] Create/edit an item - verify "Scan Type" label in form
  - [ ] Verify Active Period configuration options exist
  - [ ] Verify "Notify if missed" toggle exists
  - [ ] When toggle is ON, verify threshold fields appear
- [ ] Verify NO separate "Scan Types" resource in navigation

### Technical Verification
- [ ] No PHP errors in logs
- [ ] No JavaScript errors in browser console
- [ ] All pages load without 404 errors
- [ ] Navigation menus render correctly
- [ ] Modal windows open and close properly
- [ ] Forms submit successfully

### Permission Testing (if applicable)
- [ ] Test with different user roles to ensure permissions work
- [ ] Verify users without permissions cannot see restricted actions

## Post-Deployment Verification
- [ ] All above checks completed in production
- [ ] User feedback collected
- [ ] No regression issues reported

## Notes
Add any issues or observations here:

---

**Tested By:** _______________
**Date:** _______________
**Environment:** _______________
**Result:** [ ] Pass [ ] Fail
