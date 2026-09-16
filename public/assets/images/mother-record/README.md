The active `template-letterhead.jpg` is the exact header rendered by Microsoft Word
from `C:\Users\ACER\Downloads\Sample Header template.docx` (opened read-only with
macros disabled). The exported PDF header was captured at 600 dpi, JPEG quality 100,
using page coordinates `(72, 45, 525.96, 107)` in points. It includes both logos,
the original Times New Roman heading and horizontal rule. The shared print/PDF
template places it at its original physical size and page coordinates. Microsoft
Word and the PDF inspection tool are not runtime dependencies.

`template-footer.jpg` captures the same Word export at 600 dpi, JPEG quality 100,
using page coordinates `(70.8, 757, 526, 799)` in points. It preserves the horizontal
rule and original four centered lines, including the complete address, email,
telephone numbers and website. Its fixed 42pt height prevents wrapping or clipping;
the shared document reserves 30mm for the footer and page number on every page.

Original branding retained from the same document:

- `dswd.png`: `word/media/image1.png` (rId6)
- `bagong-pilipinas.png`: `word/media/image3.png` (rId8)

The duplicate fallback image2.png was not retained. The document's division heading,
horizontal rules and Field Office IV-A footer were transcribed into the shared Blade
record template. Images retain their original resolution and proportions; no external
logo downloads or synthesized logos are used.

The matching JPEG copies use the original pixel dimensions, a white paper background,
quality 100 and no chroma subsampling. The PDF embeds these copies because this XAMPP
installation does not enable PHP GD, which Dompdf requires for PNG rendering.
The source PNGs are retained for future use.
