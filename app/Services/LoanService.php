<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use App\Models\User;
use Carbon\Carbon;

class LoanService
{
    /**
     * Create a Loan
     *
     * @param  User  $user
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  int  $terms
     * @param  string  $processedAt
     *
     * @return Loan
     */
    public function createLoan(User $user, int $amount, string $currencyCode, int $terms, string $processedAt): Loan
    {
        $loan = Loan::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'terms' => $terms,
            'processed_at' => Carbon::parse($processedAt)->format('Y-m-d'),
            'status' => Loan::STATUS_DUE,
        ]);

        $monthlyAmount = intdiv($amount, $terms);
        $remainder     = $amount % $terms;

        for ($i = 0; $i <= $remainder; $i++) {
            $loan->scheduledRepayments()->create([
                'loan_id'    => $loan->id,
                'amount'     => ($i == $remainder && $remainder <> 0 ? $monthlyAmount+1 : $monthlyAmount),
                'outstanding_amount'     => ($i == $remainder && $remainder <> 0 ? $monthlyAmount+1 : $monthlyAmount),
                'currency_code' => $currencyCode,
                'due_date'  => Carbon::parse($processedAt)->addMonths($i+1),
                'status' => Loan::STATUS_DUE,
            ]);
        } 

        return $loan;
    }

    /**
     * Repay Scheduled Repayments for a Loan
     *
     * @param  Loan  $loan
     * @param  int  $amount
     * @param  string  $currencyCode
     * @param  string  $receivedAt
     *
     * @return ReceivedRepayment
     */
    public function repayLoan(Loan $loan, int $amount, string $currencyCode, string $receivedAt): ReceivedRepayment
    {
        $repayment = ReceivedRepayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'currency_code' => $currencyCode,
            'received_at' => $receivedAt,
        ]);

        $remaining = $amount;

        foreach ($loan->scheduledRepayments()->orderBy('due_date')->get() as $scheduled) {
            $alreadyPaid = $scheduled->amount ?? 0;

            $outstanding = $scheduled->amount - $alreadyPaid;

            if ($outstanding <= 0) {
                continue;
            }

            $applied = min($outstanding, $remaining);

            $scheduled->amount = ($scheduled->amount ?? 0) + $applied;
            $scheduled->save();

            $remaining -= $applied;

            if ($remaining <= 0) break;
        }

        return $repayment;
    }
}
