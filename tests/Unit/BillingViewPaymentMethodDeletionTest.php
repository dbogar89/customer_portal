<?php

namespace Tests\Unit;

use App\SystemSetting;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class BillingViewPaymentMethodDeletionTest extends TestCase
{
    public function test_delete_button_is_rendered_for_credit_card_when_deletion_is_allowed(): void
    {
        $view = $this->view('pages.billing.index', $this->makeViewData(true));

        $view->assertSee('/portal/billing/payment_methods/1', false);
    }

    public function test_delete_button_is_not_rendered_for_credit_card_when_deletion_is_disabled(): void
    {
        $view = $this->view('pages.billing.index', $this->makeViewData(false));

        $view->assertDontSee('/portal/billing/payment_methods/1', false);
    }

    public function test_delete_button_is_rendered_for_bank_account_when_deletion_is_allowed(): void
    {
        config(['customer_portal.enable_bank_payments' => 1]);

        $view = $this->view('pages.billing.index', $this->makeViewData(true, 'echeck'));

        $view->assertSee('/portal/billing/payment_methods/1', false);
    }

    public function test_delete_button_is_not_rendered_for_bank_account_when_deletion_is_disabled(): void
    {
        config(['customer_portal.enable_bank_payments' => 1]);

        $view = $this->view('pages.billing.index', $this->makeViewData(false, 'echeck'));

        $view->assertDontSee('/portal/billing/payment_methods/1', false);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeViewData(bool $allowDeletion, string $paymentMethodType = 'credit card'): array
    {
        $systemSetting = new SystemSetting([
            'allow_payment_method_deletion' => $allowDeletion,
            'data_usage_enabled' => false,
            'show_detailed_transactions' => false,
        ]);

        $contact = new class {
            public function getName(): string
            {
                return 'Test User';
            }
        };

        $paymentMethod = (object) [
            'id' => 1,
            'type' => $paymentMethodType,
            'identifier' => '4242',
            'auto' => 0,
            'expiration_month' => 12,
            'expiration_year' => 2030,
        ];

        $emptyPaginator = new LengthAwarePaginator([], 0, 5, 1);

        return [
            'values' => [
                'amount_due' => 0,
                'next_bill_date' => null,
                'next_bill_amount' => null,
                'total_balance' => '0.00',
                'available_funds' => '0.00',
                'payment_past_due' => false,
                'balance_minus_funds' => '0.00',
                'currentUsage' => [],
                'account_id' => 1,
            ],
            'invoices' => $emptyPaginator,
            'transactions' => $emptyPaginator,
            'paymentMethods' => [$paymentMethod],
            'systemSetting' => $systemSetting,
            'svgs' => [],
            'contact' => $contact,
        ];
    }
}
