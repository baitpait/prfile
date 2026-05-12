<?php

namespace App\Livewire;

use App\Models\Client;
use Livewire\Component;

class ClientForm extends Component
{
    public ?int $clientId = null;

    public string $business_name = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone_primary = '';

    public string $phone_secondary = '';

    public string $city = '';

    public string $country_code = 'PS';

    public string $notes = '';

    public function mount(?Client $client = null): void
    {
        abort_unless(auth()->user()->isAccountant(), 403);
        if ($client && $client->exists) {
            $this->clientId = $client->id;
            $this->business_name = $client->business_name ?? '';
            $this->first_name = $client->first_name ?? '';
            $this->last_name = $client->last_name ?? '';
            $this->email = $client->email ?? '';
            $this->phone_primary = $client->phone_primary ?? '';
            $this->phone_secondary = $client->phone_secondary ?? '';
            $this->city = $client->city ?? '';
            $this->country_code = $client->country_code ?? 'PS';
            $this->notes = $client->notes ?? '';
        }
    }

    public function save(): void
    {
        $this->validate([
            'email' => 'nullable|email',
            'phone_primary' => 'nullable|string|max:30',
        ], [], [
            'email' => 'البريد الإلكتروني',
            'phone_primary' => 'الهاتف الرئيسي',
        ]);

        if (! $this->business_name && ! $this->first_name) {
            $this->addError('business_name', 'يجب إدخال اسم الشركة أو الاسم الشخصي على الأقل');

            return;
        }

        $data = [
            'business_name' => $this->business_name ?: null,
            'first_name' => $this->first_name ?: null,
            'last_name' => $this->last_name ?: null,
            'email' => $this->email ?: null,
            'phone_primary' => $this->phone_primary ?: null,
            'phone_secondary' => $this->phone_secondary ?: null,
            'city' => $this->city ?: null,
            'country_code' => $this->country_code ?: null,
            'notes' => $this->notes ?: null,
        ];

        if ($this->clientId) {
            Client::findOrFail($this->clientId)->update($data);
            $msg = 'تم تحديث بيانات العميل';
        } else {
            Client::create($data);
            $msg = 'تم إضافة العميل بنجاح';
        }

        session()->flash('toast', $msg);
        $this->redirect(route('clients.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.client-form');
    }
}
