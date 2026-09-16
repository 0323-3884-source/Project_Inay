# Inay Kaalaman infographic learning

The mother page uses the existing `EducationalContent` model, admin publishing workflow, and `inay_kaalaman_progress` table. The September 10 migration adds four nullable metadata fields and 13 starter resources. It does not replace existing educational content or progress rows.

## Adding content

In **Admin → Educational Content**, choose the care stage and optional pregnancy/learning month, enter a title, category and description, upload a PNG/JPEG/WebP infographic, and publish it. The mother page includes published infographic uploads automatically. Images keep their original resolution in storage; the viewer scales them to the available width and provides an original-image link. An optional health campaign month is a calendar tag, independent of the learning month.

Structured content uses `infographic_sections`, an array of `[heading, body]` pairs. Blade renders escaped text as a responsive illustrated checklist. It is editable through the model; a dedicated section editor can be added to the admin form later. `database/data/infographics.php` is the initial migration snapshot, not a runtime configuration file. Add future records through the model/admin rather than editing that snapshot.

| Requested field | Existing/new field |
| --- | --- |
| title, category, description | `title`, `category`, `description` |
| image/file | Existing `infographic_path`, original name, MIME type and size |
| learning month | Existing `month` (1–10, or null for whole stage) |
| calendar/campaign month | `calendar_month` (1–12, optional) |
| publication status | Existing `is_published` |
| publication date | Existing `published_at` |
| creator | Existing `created_by_admin_id`; system starter records have no admin author |
| structured checklist | `infographic_sections` JSON |
| stable legacy identifier | `infographic_key`, unique and optional |

Keep seeded `month-N-infographic` keys and their learning months unchanged: these connect to existing monthly requirements. New resources default to `content-{id}-infographic` keys. Supplemental resources have independent saved progress and do not increase the existing required monthly totals. Whole-stage resources use learning bucket 10 with their own unique keys; `calendar_month` is never used as a progress bucket. Calendar tags are displayed throughout the year so past resources remain accessible.

## Progress and viewer

- Opening sends `in_progress`; viewing the end sends the existing completion value `reviewed`, displayed as **Natapos**.
- Completion waits until the end is visible inside the reader and all images have loaded. This tracks viewing, not medical competence or proof of understanding.
- A request queue prevents the same card's start/completion writes racing in the browser. The server serializes a mother's writes with a transaction and row lock, retains the existing unique index, and never downgrades completed progress.
- The authenticated mother's session determines ownership. Invalid, unpublished, and mismatched infographic identifiers are rejected.
- Failed saves keep the last confirmed badge and show a retry button. A failed image load cannot trigger completion.
- Native dialog behavior supplies keyboard focus containment and Escape dismissal. The viewer restores focus to its opener and fits the visual viewport, including mobile zoom/rotation.
- Existing lessons, video progress, uploads, navigation, medical activities and PDF routes remain available.

## Validation

Run the focused Laravel tests:

```powershell
php artisan test --compact --filter="InfographicLearningTest|EducationalContentManagementTest|test_kaalaman|test_staff_cannot_access_mother_only_monitoring_and_kaalaman_routes"
```

Browser checks use an HTML fixture generated from an in-memory test database and mock only the progress responses. The Laravel tests check actual database persistence separately. They do not use real mother records.

```powershell
$env:INAY_BROWSER_FIXTURE = Join-Path (Get-Location) 'storage/app/infographic-browser.html'
php artisan test --compact --filter=test_library_renders_published_content_and_preserves_legacy_progress
Remove-Item Env:INAY_BROWSER_FIXTURE
# In another terminal, serve static assets:
python -m http.server 8765 --bind 127.0.0.1 --directory public
# With Chromium available (set CHROME_PATH if needed):
node tests/js/inay-infographics.browser.mjs
```

The browser runner isolates external requests, uses a dedicated temporary browser profile, and checks phone/tablet/desktop sizing, automatic completion, retry, focus restoration and reopening. Screenshots are written under `storage/app/infographic-browser/`.

## Education references

The initial pregnancy checklist follows the requested copy. General supporting references are [WHO antenatal care](https://www.who.int/publications/i/item/9789241549912), [nutrition counselling during pregnancy](https://www.who.int/tools/elena/interventions/nutrition-counselling-pregnancy), [WHO postnatal care](https://www.who.int/publications/i/item/9789240045989), and [essential newborn care](https://www.who.int/teams/maternal-newborn-child-adolescent-health-and-ageing/newborn-health/essential-newborn-care). Every infographic includes the emergency reminder and states that it does not replace professional medical advice.
