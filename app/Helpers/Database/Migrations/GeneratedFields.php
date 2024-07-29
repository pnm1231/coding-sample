<?php

/**
 * Explanation for IxDF
 *
 * I am using generated fields in the database to calculate the discount, additional charge, and total of many models.
 * So I am calling methods in this helper class from migrations to prevent code repetition.
 *
 * Example of a primary table: purchase_orders
 * Example of a secondary table: purchase_order_products
 */

namespace App\Helpers\Database\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GeneratedFields
{
    public static function addToPrimaryTable(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) {
            $table->decimal('discount', 10)
                ->after('discount_rate')
                ->virtualAs('
                    CASE 
                        WHEN `sub_total` > 0 AND `discount_calculation_method` IS NOT NULL AND `discount_rate` IS NOT NULL THEN
                            CASE 
                                WHEN `discount_calculation_method` = 1 THEN GREATEST(0, `sub_total` * `discount_rate` / 100)
                                WHEN `discount_calculation_method` = 2 THEN `discount_rate`
                            END
                        ELSE 0
                    END
                ');

            $table->after('additional_charge_rate', function (Blueprint $table): void {
                $table->decimal('additional_charge', 10)
                    ->virtualAs('
                        CASE 
                            WHEN `sub_total` > 0 AND `additional_charge_calculation_method` IS NOT NULL AND `additional_charge_rate` IS NOT NULL THEN
                                CASE
                                    WHEN `additional_charge_calculation_method` = 1 THEN GREATEST(0, (`sub_total` - `discount`) * `additional_charge_rate` / 100)
                                    WHEN `additional_charge_calculation_method` = 2 THEN `additional_charge_rate`
                                END
                            ELSE 0
                        END
                    ');

                $table->decimal('total', 10)
                    ->virtualAs('GREATEST(0, `sub_total` - `discount` + `additional_charge`)');
            });
        });
    }

    public static function removeFromPrimaryTable(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn('discount');
            $table->dropColumn('additional_charge');
            $table->dropColumn('total');
        });
    }

    public static function addToSecondaryTable(string $tableName, string $priceKey, bool $hasTax = false): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($priceKey, $hasTax) {
            $table->decimal('sub_total', 10)
                ->after('quantity')
                ->virtualAs("`quantity` * `$priceKey`");

            $table->decimal('discount', 10)
                ->virtualAs('
                    CASE 
                        WHEN `sub_total` > 0 AND `discount_calculation_method` IS NOT NULL AND `discount_rate` IS NOT NULL THEN
                            CASE
                                WHEN `discount_calculation_method` = 1 THEN GREATEST(0, `sub_total` * `discount_rate` / 100)
                                WHEN `discount_calculation_method` = 2 THEN `discount_rate`
                            END
                        ELSE 0
                    END
                ');

            $totalCalculation = '`sub_total` - `discount`';

            if ($hasTax) {
                $totalCalculation .= ' + `tax`';
            }

            $table->decimal('total', 10)
                ->virtualAs("GREATEST(0, $totalCalculation)");
        });
    }

    public static function removeFromSecondaryTable(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn('sub_total');
            $table->dropColumn('discount');
            $table->dropColumn('total');
        });
    }
}

