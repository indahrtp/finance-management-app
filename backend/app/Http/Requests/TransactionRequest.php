<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Coa;
use App\Enums\CategoryType;

class TransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'debit'  => $this->debit ?: 0,
            'credit' => $this->credit ?: 0,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:today'],
            'coa_id' => ['required', 'exists:coa,id'],
            'wallet_id' => ['required', 'exists:wallets,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'debit' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'credit' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $debit = (float) $this->debit;
                $credit = (float) $this->credit;

                if (($debit > 0) === ($credit > 0)) {
                    $validator->errors()->add('debit', 'Isi salah satu saja: debit atau credit, dan nilainya harus lebih dari 0.');
                    return;
                }

                $type = Coa::with('category')->find($this->coa_id)->category->type;

                if ($type === CategoryType::Income && $credit <= 0) {
                    $validator->errors()->add('credit', 'COA bertipe income hanya boleh memakai credit.');
                }

                if ($type === CategoryType::Expense && $debit <= 0) {
                    $validator->errors()->add('debit', 'COA bertipe expense hanya boleh memakai debit.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'date.before_or_equal' => 'Tanggal transaksi tidak boleh melebihi hari ini.',
        'date.after_or_equal' => 'Tanggal transaksi tidak boleh sebelum 1 Januari 2000.',
        ];
    }
}
