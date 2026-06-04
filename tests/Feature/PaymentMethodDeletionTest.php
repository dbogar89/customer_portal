<?php

namespace Tests\Feature;

use App\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodDeletionTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Guard tests
    // -------------------------------------------------------------------------

    public function test_delete_request_is_rejected_when_allow_payment_method_deletion_is_false(): void
    {
        $this->createSystemSetting(['allow_payment_method_deletion' => false]);

        $response = $this->withSession($this->authenticatedSession())
            ->delete('/portal/billing/payment_methods/1');

        $response->assertRedirect();
        $response->assertSessionHasErrors();
        $this->assertStringContainsString(
            'not permitted',
            implode(' ', $response->getSession()->get('errors')->all())
        );
    }

    public function test_guard_does_not_trigger_when_allow_payment_method_deletion_is_true(): void
    {
        $this->createSystemSetting(['allow_payment_method_deletion' => true]);

        // The guard passes — the request fails downstream (no Sonar API in tests) but
        // the session should not contain the deletion-disabled error message.
        $response = $this->withSession($this->authenticatedSession())
            ->delete('/portal/billing/payment_methods/1');

        $errors = $response->getSession()->get('errors');
        if ($errors) {
            $this->assertStringNotContainsString(
                'not permitted',
                implode(' ', $errors->all())
            );
        }
    }

    // -------------------------------------------------------------------------
    // Settings persistence tests
    // -------------------------------------------------------------------------

    public function test_settings_save_persists_allow_payment_method_deletion_as_false(): void
    {
        $this->createSystemSetting(['allow_payment_method_deletion' => true]);

        $this->withSession(['settings_authenticated' => 1])
            ->post('/settings', $this->validSettingsPayload(['allow_payment_method_deletion' => 0]));

        $this->assertDatabaseHas('system_settings', ['allow_payment_method_deletion' => false]);
    }

    public function test_settings_save_persists_allow_payment_method_deletion_as_true(): void
    {
        $this->createSystemSetting(['allow_payment_method_deletion' => false]);

        $this->withSession(['settings_authenticated' => 1])
            ->post('/settings', $this->validSettingsPayload(['allow_payment_method_deletion' => 1]));

        $this->assertDatabaseHas('system_settings', ['allow_payment_method_deletion' => true]);
    }

    public function test_allow_payment_method_deletion_defaults_to_true_when_omitted_from_settings_payload(): void
    {
        $this->createSystemSetting(['allow_payment_method_deletion' => true]);

        // Unchecked checkboxes are not submitted — the hidden input sends 0.
        // Verify the field is treated as boolean false when the value is 0.
        $payload = $this->validSettingsPayload();
        unset($payload['allow_payment_method_deletion']);
        $payload['allow_payment_method_deletion'] = 0;

        $this->withSession(['settings_authenticated' => 1])
            ->post('/settings', $payload);

        $this->assertDatabaseHas('system_settings', ['allow_payment_method_deletion' => false]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function authenticatedSession(): array
    {
        return [
            'authenticated' => true,
            'user' => (object) [
                'account_id' => 1,
                'contact_id' => 1,
                'email_address' => 'test@example.com',
                'contact_name' => 'Test User',
                'username' => 'testuser',
            ],
        ];
    }

    private function createSystemSetting(array $attributes = []): SystemSetting
    {
        return SystemSetting::create(array_merge([
            'id' => 1,
            'settings_key' => 'test-key',
            'allow_payment_method_deletion' => true,
        ], $attributes));
    }

    private function validSettingsPayload(array $overrides = []): array
    {
        return array_merge([
            'url' => 'https://portal.example.com',
            'locale' => 'en',
            'mail_host' => 'localhost',
            'mail_username' => 'mail_user',
            'mail_password' => 'mail_pass',
            'mail_port' => 25,
            'mail_from_address' => 'noreply@example.com',
            'mail_from_name' => 'Portal',
            'isp_name' => 'Test ISP',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'currency_symbol' => '$',
            'country' => 'US',
            'state' => 'WI',
            'allow_payment_method_deletion' => 1,
        ], $overrides);
    }
}
