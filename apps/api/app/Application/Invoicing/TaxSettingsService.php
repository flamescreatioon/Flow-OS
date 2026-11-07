<?php

namespace App\Application\Invoicing;

use App\Application\Audit\AuditLogger;
use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Enums\TaxMode;
use App\Models\Organization;
use App\Models\TaxSetting;
use Illuminate\Support\Facades\DB;
use LogicException;

final class TaxSettingsService
{
    /**
     * The organization's tax configuration, or the defaults when none is
     * stored yet. Reading never persists a row.
     */
    public function show(Organization $organization): TaxSetting
    {
        $settings = TaxSetting::query()->where('organization_id', $organization->id)->first();

        if ($settings) {
            return $settings;
        }

        return new TaxSetting([
            'organization_id' => $organization->id,
            'tax_enabled' => false,
            'tax_rate_percent' => null,
            'tax_mode' => TaxMode::Exclusive->value,
        ]);
    }

    public function update(Organization $organization, array $data, $actor): TaxSetting
    {
        return DB::transaction(function () use ($organization, $data, $actor) {
            $settings = TaxSetting::query()->where('organization_id', $organization->id)->first();

            if (! $settings) {
                $settings = new TaxSetting(['organization_id' => $organization->id]);
            }

            $enabled = array_key_exists('tax_enabled', $data)
                ? (bool) $data['tax_enabled']
                : $settings->tax_enabled;

            $rate = null;

            if (array_key_exists('tax_rate_percent', $data) && $data['tax_rate_percent'] !== null) {
                $rate = UnitPrice::parse($data['tax_rate_percent']);

                if ($rate->isNegative() || $rate->isZero()) {
                    throw new LogicException('The tax rate must be a positive percentage.');
                }
            } elseif ($enabled && $settings->tax_rate_percent !== null) {
                $rate = UnitPrice::of($settings->tax_rate_percent);
            }

            if ($enabled && $rate === null) {
                throw new LogicException('A tax rate is required when tax is enabled.');
            }

            $mode = TaxMode::from($data['tax_mode'] ?? ($settings->tax_mode?->value ?? TaxMode::Exclusive->value));

            $settings->tax_enabled = $enabled;
            $settings->tax_rate_percent = $rate?->scaledValue();
            $settings->tax_mode = $mode;
            $settings->save();

            app(AuditLogger::class)->record(
                $organization,
                $actor,
                'invoicing.tax_settings_updated',
                'tax_setting',
                $settings->id,
                [
                    'organization_id' => $organization->id,
                    'tax_enabled' => $enabled,
                    'tax_mode' => $mode->value,
                ]
            );

            return $settings;
        });
    }
}
