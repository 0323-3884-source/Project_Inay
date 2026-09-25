# Clinic midwife assignments

In **Program Staff → Clinic Schedule → Scheduling Profile**, set the worker's barangay and facility, then select a midwife or choose **Enter a midwife not yet registered**. Save Profile applies the assignment to new bookings. Choose **No assigned midwife** to remove it from future bookings.

Registered midwives keep their existing staff account, contact information, available hours, daily limits, and availability. Selection never changes another worker's profile. Manual personnel records have a name, facility, barangay, optional contact, and availability; they do not create login accounts. Their creator can update availability by selecting the saved record.

The midwife must belong to the same facility and barangay. Facility matching ignores case and extra spaces. Manual entry checks registered names first, then reuses an existing normalized manual name; ambiguous names or a matching identity at another facility require selecting/resolving the existing record. Existing details are not overwritten by a repeated entry.

`healthcare_facilities` supports many `program_staff` and manual `midwife_profiles`. A registered midwife profile links uniquely to its existing staff account; its facility and availability come from that account. `program_staff.assigned_midwife_id` identifies the explicitly selected colleague. No midwife is inferred simply because multiple people share a facility.

Both booking paths save facility and midwife foreign keys plus `care_team_snapshot` on new appointments. The snapshot preserves the displayed personnel, facility, and barangay if profiles later change. Unavailable midwives or midwives who moved to another facility are omitted from new assignments. Booking a registered midwife directly records that person as the assigned midwife. This association does not create a second booking or change the midwife's independent appointment hours.

The mother's **My Appointments** cards and staff request cards show the saved team. Previous appointments are preserved without guessing historical midwife assignments. The earlier local sample midwife remains explicitly labeled as test data and is never automatically assigned to another worker.

## Installation and verification

Run `php artisan migrate` when deploying the updated code. The additive migration links staff with existing facility text without replacing that text or updating existing appointments.

Run `php artisan test --filter="AppointmentCareTeamTest|ClinicScheduleTest"` for booking, duplicate detection, availability, shared facilities, permissions, and historical assignment checks.

For desktop/mobile browser checks in PowerShell:

```powershell
$env:INAY_CARE_TEAM_FIXTURES = '1'
php artisan test --filter=AppointmentCareTeamTest
node tests/js/appointment-care-team.browser.mjs
```

The browser check uses installed Chrome and local test fixtures, never real account sessions. Screenshots are written under `storage/framework/testing/care-team/`.
