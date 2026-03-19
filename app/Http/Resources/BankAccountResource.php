<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $iban = $this->iban ?? '';
        $maskedIban = strlen($iban) > 4
            ? str_repeat('*', strlen($iban) - 4).substr($iban, -4)
            : $iban;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'iban' => $this->iban,
            'iban_masked' => $maskedIban,
            'bic' => $this->bic,
            'balance' => (float) $this->balance,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
