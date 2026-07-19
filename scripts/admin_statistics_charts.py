"""Export Project INAY admin statistics chart data.

This helper keeps the web dashboard in Laravel/Blade, but lets you inspect the
same chart data from Python for testing and reporting.
"""

from __future__ import annotations

import argparse
import json
import subprocess
from collections import Counter
from datetime import date, datetime
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[1]
DEFAULT_OUTPUT = ROOT / "storage" / "app" / "admin-statistics" / "python_chart_data.json"


def run_php_export() -> dict[str, Any]:
    php_code = r"""<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$mothers = App\Models\Mother::query()
    ->select(['id', 'barangay', 'pregnancy_status', 'is_4ps_beneficiary', 'created_at'])
    ->get()
    ->map(fn ($mother) => [
        'id' => $mother->id,
        'barangay' => $mother->barangay,
        'pregnancy_status' => $mother->pregnancy_status,
        'is_4ps_beneficiary' => (bool) $mother->is_4ps_beneficiary,
        'created_at' => $mother->created_at?->toDateString(),
    ])
    ->values();

$riskRecords = App\Models\MaternalMonitoringRecord::query()
    ->select(['mother_id', 'risk_level', 'recorded_at', 'created_at'])
    ->orderByDesc('recorded_at')
    ->orderByDesc('created_at')
    ->get()
    ->map(fn ($record) => [
        'mother_id' => $record->mother_id,
        'risk_level' => $record->risk_level,
        'recorded_at' => $record->recorded_at?->toDateString(),
        'created_at' => $record->created_at?->toDateString(),
    ])
    ->values();

echo json_encode(['mothers' => $mothers, 'risk_records' => $riskRecords], JSON_THROW_ON_ERROR);
?>"""

    completed = subprocess.run(
        ["php"],
        input=php_code,
        cwd=ROOT,
        text=True,
        capture_output=True,
        check=True,
    )

    return json.loads(completed.stdout)


def normalize_barangay(value: str | None) -> str:
    text = " ".join((value or "").strip().split())
    if not text:
        return "Unspecified"

    lowered = text.lower()
    if lowered.startswith("barangay "):
        text = text[9:]

    words = []
    for word in text.split():
        plain = word.rstrip(".").lower()
        if plain == "sta":
            words.append("Santa")
        elif plain == "sto":
            words.append("Santo")
        elif plain == "st":
            words.append("Santa")
        else:
            words.append(word[:1].upper() + word[1:].lower())

    return " ".join(words) or "Unspecified"


def parse_date(value: str | None) -> date | None:
    if not value:
        return None

    try:
        return datetime.strptime(value[:10], "%Y-%m-%d").date()
    except ValueError:
        return None


def month_key(day: date) -> str:
    return f"{day.year:04d}-{day.month:02d}"


def add_months(day: date, offset: int) -> date:
    month_index = (day.year * 12 + (day.month - 1)) + offset
    return date(month_index // 12, (month_index % 12) + 1, 1)


def build_statistics(raw: dict[str, Any]) -> dict[str, Any]:
    mothers = raw["mothers"]
    risk_by_mother: dict[int, str] = {}

    for record in raw["risk_records"]:
        mother_id = int(record["mother_id"])
        risk_by_mother.setdefault(mother_id, (record.get("risk_level") or "").lower())

    pregnant = [mother for mother in mothers if mother.get("pregnancy_status") == "pregnant"]
    active_total = len(pregnant)
    barangay_counts = Counter(normalize_barangay(mother.get("barangay")) for mother in pregnant)

    barangays = [
        {
            "barangay": barangay,
            "total": total,
            "percentage": round((total / active_total) * 100, 1) if active_total else 0,
            "total_4ps": sum(
                1
                for mother in pregnant
                if normalize_barangay(mother.get("barangay")) == barangay and mother.get("is_4ps_beneficiary")
            ),
        }
        for barangay, total in barangay_counts.items()
    ]
    barangays.sort(key=lambda row: (-row["total"], row["barangay"]))

    first_month = add_months(date.today().replace(day=1), -11)
    month_counts = Counter(
        month_key(created)
        for mother in pregnant
        if (created := parse_date(mother.get("created_at"))) and created >= first_month
    )
    monthly = []
    for offset in range(12):
        current = add_months(first_month, offset)
        key = month_key(current)
        monthly.append(
            {
                "key": key,
                "label": current.strftime("%b %Y"),
                "short_label": current.strftime("%b"),
                "total": month_counts[key],
            }
        )

    total_mothers = len(mothers)
    total_4ps = sum(1 for mother in mothers if mother.get("is_4ps_beneficiary"))

    return {
        "framework": "Laravel 12 + Blade renders the live dashboard; this Python 3 script exports the chart data.",
        "summary": {
            "total_mothers": total_mothers,
            "total_4ps": total_4ps,
            "total_non_4ps": max(0, total_mothers - total_4ps),
            "barangays_represented": len({normalize_barangay(mother.get("barangay")) for mother in mothers}),
            "active_pregnancies": active_total,
            "high_risk_pregnancies": sum(1 for mother in pregnant if risk_by_mother.get(int(mother["id"])) == "high"),
            "low_risk_pregnancies": sum(1 for mother in pregnant if risk_by_mother.get(int(mother["id"])) == "low"),
            "percentage_4ps": round((total_4ps / total_mothers) * 100, 1) if total_mothers else 0,
        },
        "pregnant_by_barangay": barangays,
        "barangay_ranking": barangays[:8],
        "monthly_registrations": monthly,
    }


def main() -> None:
    parser = argparse.ArgumentParser(description="Export Project INAY admin chart data with Python.")
    parser.add_argument("--output", type=Path, default=DEFAULT_OUTPUT, help="JSON output path.")
    args = parser.parse_args()

    chart_data = build_statistics(run_php_export())
    output = args.output if args.output.is_absolute() else ROOT / args.output
    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(json.dumps(chart_data, indent=2), encoding="utf-8")
    print(f"Wrote {output}")


if __name__ == "__main__":
    main()
