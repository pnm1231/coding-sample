<?php

/**
 * Explanation
 *
 * When it comes to numbering documents, the system behaves in 3 different ways.
 * 1. If the document has a "Company" attached, it will use the company's numbering settings.
 * 2. If the document has no "Company" attached, it will use the organization's numbering settings.
 * 3. If neither settings are set, it will use the application's default settings.
 *
 * This trait is used to test the above behavior for different models.
 */

namespace App\Traits\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Administration\Models\Company;
use Modules\Administration\Models\Organization;
use Modules\Inventory\Models\InventorySetting;
use Modules\Sales\Models\SalesSetting;

trait HasNumberingTests
{
    abstract protected function getStartingNumberKey(): string;

    abstract protected function getPrefixKey(): string;

    abstract protected function getSettingsRelationship(Organization $organization): InventorySetting|SalesSetting;

    abstract protected function getModelName(): string;

    abstract protected function getModelFactory(): Factory;

    abstract protected function hasCompanyNumbers(): bool;

    public function test_it_uses_the_company_starting_number_over_settings_on_create(): void
    {
        if (! $this->hasCompanyNumbers()) {
            $this->markTestSkipped('Company numbers are not available for model: '.Str::of($this->getModelName())->afterLast('\\'));
        }

        $organization = Organization::factory()->create();

        $companyStartingNumber = $this->faker->randomNumber(nbDigits: 2, strict: true);

        $company = Company::factory()
            ->set($this->getStartingNumberKey(), $companyStartingNumber)
            ->create();

        $settingsNumber = $this->faker->randomNumber(nbDigits: 3, strict: true);

        $this->getSettingsRelationship($organization)->update([
            $this->getStartingNumberKey() => $settingsNumber,
        ]);

        $model = $this->getModelFactory()
            ->for($organization)
            ->for($company)
            ->create();

        $this->assertDatabaseHas(self::getModelName(), [
            'id' => $model->id,
            'number' => $companyStartingNumber,
        ]);
    }

    public function test_it_uses_the_next_number_when_company_starting_number_is_smaller_on_create(): void
    {
        if (! $this->hasCompanyNumbers()) {
            $this->markTestSkipped('Company numbers are not available for model: '.Str::of($this->getModelName())->afterLast('\\'));
        }

        $organization = Organization::factory()->create();

        $companyStartingNumber = $this->faker->randomNumber(nbDigits: 2, strict: true);

        $company = Company::factory()
            ->set(self::getStartingNumberKey(), $companyStartingNumber)
            ->create();

        self::getModelFactory()
            ->for($organization)
            ->for($company)
            ->create();

        $settingsNumber = $this->faker->randomNumber(nbDigits: 3, strict: true);

        self::getSettingsRelationship($organization)->update([
            self::getStartingNumberKey() => $settingsNumber,
        ]);

        $model = self::getModelFactory()
            ->for($organization)
            ->for($company)
            ->create();

        $this->assertDatabaseHas(self::getModelName(), [
            'id' => $model->id,
            'number' => $companyStartingNumber + 1,
        ]);
    }

    public function test_it_uses_the_settings_starting_number_on_create(): void
    {
        $organization = Organization::factory()->create();

        $settingsNumber = $this->faker->randomNumber(nbDigits: 3, strict: true);

        self::getSettingsRelationship($organization)->update([
            self::getStartingNumberKey() => $settingsNumber,
        ]);

        $model = self::getModelFactory()
            ->for($organization)
            ->when($this->hasCompanyNumbers(), fn (Factory $model) => $model->set('company_id', null))
            ->create();

        $this->assertDatabaseHas(self::getModelName(), [
            'id' => $model->id,
            'number' => $settingsNumber,
        ]);
    }

    public function test_it_uses_the_next_number_when_settings_starting_number_is_smaller_on_create(): void
    {
        $organization = Organization::factory()->create();

        $settingsNumber = $this->faker->randomNumber(nbDigits: 3, strict: true);

        self::getSettingsRelationship($organization)->update([
            self::getStartingNumberKey() => $settingsNumber,
        ]);

        self::getModelFactory()
            ->for($organization)
            ->when($this->hasCompanyNumbers(), fn (Factory $model) => $model->set('company_id', null))
            ->create();

        $model = self::getModelFactory()
            ->for($organization)
            ->when($this->hasCompanyNumbers(), fn (Factory $model) => $model->set('company_id', null))
            ->create();

        $this->assertDatabaseHas(self::getModelName(), [
            'id' => $model->id,
            'number' => $settingsNumber + 1,
        ]);
    }

    public function test_is_uses_the_company_prefix_when_company_is_set_on_create(): void
    {
        if (! $this->hasCompanyNumbers()) {
            $this->markTestSkipped('Company numbers are not available for model: '.Str::of($this->getModelName())->afterLast('\\'));
        }

        $organization = Organization::factory()->create();

        $companyPrefix = $this->faker->randomNumber(nbDigits: 4, strict: true);

        $company = Company::factory()
            ->set($this->getPrefixKey(), $companyPrefix)
            ->create();

        $model = self::getModelFactory()
            ->for($organization)
            ->for($company)
            ->create();

        $this->assertDatabaseHas(self::getModelName(), [
            'id' => $model->id,
            'number_prefix' => $companyPrefix,
        ]);
    }

    public function test_is_uses_the_settings_prefix_when_company_prefix_is_not_set_on_create(): void
    {
        if (! $this->hasCompanyNumbers()) {
            $this->markTestSkipped('Company numbers are not available for model: '.Str::of($this->getModelName())->afterLast('\\'));
        }

        $organization = Organization::factory()->create();

        $settingsPrefix = $this->faker->randomNumber(nbDigits: 4, strict: true);

        self::getSettingsRelationship($organization)->update([
            $this->getPrefixKey() => $settingsPrefix,
        ]);

        $company = Company::factory()
            ->set($this->getPrefixKey(), null)
            ->create();

        $model = self::getModelFactory()
            ->for($organization)
            ->for($company)
            ->create();

        $this->assertDatabaseHas(self::getModelName(), [
            'id' => $model->id,
            'number_prefix' => $settingsPrefix,
        ]);
    }

    public function test_is_uses_the_settings_prefix_when_company_is_not_set_on_create(): void
    {
        if (! $this->hasCompanyNumbers()) {
            $this->markTestSkipped('Company numbers are not available for model: '.Str::of($this->getModelName())->afterLast('\\'));
        }

        $organization = Organization::factory()->create();

        $settingsPrefix = $this->faker->randomNumber(nbDigits: 4, strict: true);

        self::getSettingsRelationship($organization)->update([
            $this->getPrefixKey() => $settingsPrefix,
        ]);

        $model = self::getModelFactory()
            ->for($organization)
            ->when($this->hasCompanyNumbers(), fn (Factory $model) => $model->set('company_id', null))
            ->create();

        $this->assertDatabaseHas(self::getModelName(), [
            'id' => $model->id,
            'number_prefix' => $settingsPrefix,
        ]);
    }
}
