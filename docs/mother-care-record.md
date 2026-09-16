# Mother and child record printing

Print Record opens a generated PDF in a separate frame; Export PDF downloads the
same document. Both reuse MotherCareRecordPdf (Dompdf), the existing form branding,
and the A4 portrait stylesheet. The dashboard is never sent to the renderer.

## Mother tabs

The shared JavaScript reads the Mother tab with aria-selected="true" at click time
and sends its data-casefile-tab value as the section query parameter to the existing
staff.mothers.print or staff.mothers.pdf route. Allowed values are overview,
monitoring, learning-documents, and notes. Requests without a section use Overview;
invalid values return validation errors. The Blade document includes mother
identification and only the selected section. No child data is included.

Overview contains maternal information, latest vitals, saved screening explanations,
health progress, and a printable table of the weight/BP trends. Monitoring includes
saved measurements and their screening details. Learning & Documents contains
monthly progress, uploaded document names/dates, and prenatal checkup information.
Notes uses the latest monitoring note, matching the Notes tab.

## Child record

The Neonatal profile has Print Record and Export PDF beside Edit Child and Update
Growth. They call staff.neonatal.print or staff.neonatal.pdf with the selected infant
ID. AuthController::staffChildRecord renders records.child-care independently of
the mother template. It contains the child's identity, mother name, birth details,
latest measurements, growth history, vaccine records/status, saved health alerts,
and the existing growth assessment. Missing values are marked as not provided.

All four routes require an approved staff account and an existing casefile
assignment to the mother. Generation reads current database values without changing
records and returns private, no-store responses. Both document templates share
records.partials.branding and resources/css/mother-care-record.css. The JavaScript
handles downloads, PDF printing, loading/error states and an open-document fallback.

## Verification

- php artisan test --compact --filter=MotherCareRecordTest
- php artisan test --compact --filter="StaffCasefilesTest|NeonatalVaccinesTest"
- node --test tests/js/mother-care-record.test.mjs

Feature coverage checks section exclusion, print/download equivalence, saved child
data, access restrictions, invalid tabs, PDF generation, and long A4 monitoring
history. JavaScript coverage checks active-tab changes and independent child URLs.
