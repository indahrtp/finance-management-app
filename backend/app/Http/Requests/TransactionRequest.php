<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
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
            'date' => ['required', 'date'],
            'coa_id' => ['required', 'exists:coa,id'],
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
}
