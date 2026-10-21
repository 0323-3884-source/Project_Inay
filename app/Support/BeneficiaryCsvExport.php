<?php

namespace App\Support;

use App\Models\Mother;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BeneficiaryCsvExport
{
    public static function download(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            $motherFields = ['first_name', 'middle_name', 'last_name', 'email', 'age', 'barangay', 'municipality_city', 'contact_number', 'civil_status', 'gravidity', 'parity', 'blood_type', 'pregnancy_status', 'last_menstrual_period', 'is_4ps_beneficiary', 'four_ps_household_number', 'dswd_entry_number'];
            $childFields = ['full_name', 'sex', 'birth_date', 'birth_weight', 'birth_height', 'blood_type', 'facility', 'notes'];
            $growthFields = ['measured_at', 'age_months', 'weight', 'height', 'head_circumference', 'temperature', 'remarks'];
            $vaccineFields = ['vaccine_group', 'vaccine_name', 'dose_label', 'due_date', 'status', 'administered_at', 'facility', 'lot_number', 'vaccinator', 'remarks'];
            $headers = ['Record Type', 'Mother Case No.', 'Child Case No.'];
            foreach (['Mother' => $motherFields, 'Child' => $childFields, 'Growth' => $growthFields, 'Vaccine' => $vaccineFields] as $prefix => $fields) {
                foreach ($fields as $field) {
                    $headers[] = $prefix.' '.ucwords(str_replace('_', ' ', $field));
                }
            }
            fputcsv($output, $headers, ',', '"', '');
            $values = static function ($model, array $fields): array {
                return array_map(static function ($field) use ($model) {
                    $value = $model?->getAttribute($field);
                    return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
                }, $fields);
            };
            $write = static function ($mother, $child = null, $growth = null, $vaccine = null) use ($output, $values, $motherFields, $childFields, $growthFields, $vaccineFields) {
                $row = array_merge([
                    $vaccine ? 'Vaccine' : ($growth ? 'Growth' : ($child ? 'Child' : 'Mother')),
                    'INAY-'.str_pad((string) $mother->id, 5, '0', STR_PAD_LEFT),
                    $child ? 'CH-'.str_pad((string) $child->id, 5, '0', STR_PAD_LEFT) : '',
                ], $values($mother, $motherFields), $values($child, $childFields), $values($growth, $growthFields), $values($vaccine, $vaccineFields));
                $row = array_map(static function ($value) {
                    $value = (string) ($value ?? '');
                    // Preserve phone/household numbers and prevent spreadsheet formula execution.
                    return preg_match('/^0\d|^[\s]*[=+@-]|^[\t\r\n]/u', $value) ? "'".$value : $value;
                }, $row);
                fputcsv($output, $row, ',', '"', '');
            };
            foreach (Mother::with(['infants.growthRecords', 'infants.vaccineRecords'])->lazyById(100) as $mother) {
                $write($mother);
                foreach ($mother->infants as $child) {
                    $write($mother, $child);
                    foreach ($child->growthRecords as $growth) {
                        $write($mother, $child, $growth);
                    }
                    foreach ($child->vaccineRecords as $vaccine) {
                        $write($mother, $child, null, $vaccine);
                    }
                }
            }
            fclose($output);
        }, 'all-beneficiaries-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
