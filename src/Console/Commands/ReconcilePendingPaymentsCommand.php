<?php

namespace VendorName\MultiPayment\Console\Commands;

use Illuminate\Console\Command;
use VendorName\MultiPayment\Models\PaymentTransaction;
use VendorName\MultiPayment\Facades\MultiPayment;
use Illuminate\Support\Facades\Log;
use Exception;

class ReconcilePendingPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:reconcile-pending {--days=1 : Number of days to look back}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queries gateways to verify the status of pending payment transactions and syncs the database.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        
        $this->info("Fetching pending transactions from the last {$days} days...");

        $pendingTransactions = PaymentTransaction::where('status', 'pending')
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('transaction_id')
            ->get();

        if ($pendingTransactions->isEmpty()) {
            $this->info("No pending transactions found for reconciliation.");
            return Command::SUCCESS;
        }

        $this->withProgressBar($pendingTransactions, function ($transaction) {
            try {
                // Determine driver based on transaction
                $driver = MultiPayment::driver($transaction->gateway);
                
                // Verify status via the appropriate driver
                // Drivers normally expect an array payload representing webhook data or ID data
                // E.g., Razorpay uses 'razorpay_order_id' or similar, but the verify() method expects specific keys.
                // Our unified package might need adapting for generic verify using transaction_id, 
                // but standard verify() usually takes the payload. Let's abstract a verifyById or use a standard structure.
                
                // Since our verify() method expects payload from frontend callback, we should pass standard keys 
                // based on gateway. For a direct API query, many gateways just need the ID.
                $payload = ['transaction_id' => $transaction->transaction_id];
                
                if ($transaction->gateway === 'stripe') {
                    $payload['payment_intent'] = $transaction->transaction_id;
                } elseif ($transaction->gateway === 'cashfree') {
                    $payload['order_id'] = $transaction->receipt_id; // Cashfree uses order_id
                } elseif ($transaction->gateway === 'razorpay') {
                    $payload['razorpay_order_id'] = $transaction->transaction_id;
                }

                $response = $driver->verify($payload);
                
                // If verified successfully
                if ($response['success']) {
                    $transaction->update([
                        'status' => 'captured',
                        'raw_response' => array_merge($transaction->raw_response ?? [], ['reconciliation' => $response['raw']])
                    ]);
                } else {
                    $transaction->update(['status' => 'failed']);
                }

            } catch (Exception $e) {
                Log::channel(config('multi-payment.logging.channel', 'daily'))
                    ->error("Reconciliation failed for Transaction {$transaction->id} ({$transaction->gateway}): " . $e->getMessage());
                
                // Optional: mark as failed if verification throws an exception 
                // indicating it definitely failed at gateway level vs network error.
            }
        });

        $this->newLine();
        $this->info("Reconciliation complete.");

        return Command::SUCCESS;
    }
}
