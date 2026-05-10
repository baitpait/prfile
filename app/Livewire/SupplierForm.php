<?php
namespace App\Livewire;

use App\Models\Supplier;
use Livewire\Component;

class SupplierForm extends Component
{
    public ?int $supplierId = null;

    public string $business_name   = '';
    public string $first_name      = '';
    public string $last_name       = '';
    public string $email           = '';
    public string $phone_primary   = '';
    public string $phone_secondary = '';
    public string $city            = '';
    public string $country_code    = 'PS';
    public string $notes           = '';

    public function mount(?Supplier $supplier = null): void
    {
        abort_unless(auth()->user()->isAccountant(), 403);
        if ($supplier && $supplier->exists) {
            $this->supplierId     = $supplier->id;
            $this->business_name  = $supplier->business_name  ?? '';
            $this->first_name     = $supplier->first_name     ?? '';
            $this->last_name      = $supplier->last_name      ?? '';
            $this->email          = $supplier->email          ?? '';
            $this->phone_primary  = $supplier->phone_primary  ?? '';
            $this->phone_secondary= $supplier->phone_secondary?? '';
            $this->city           = $supplier->city           ?? '';
            $this->country_code   = $supplier->country_code   ?? 'PS';
            $this->notes          = $supplier->notes          ?? '';
        }
    }

    public function save(): void
    {
        $this->validate([
            'email'         => 'nullable|email',
            'phone_primary' => 'nullable|string|max:30',
        ], [], [
            'email'         => 'البريد الإلكتروني',
            'phone_primary' => 'الهاتف الرئيسي',
        ]);

        if (!$this->business_name && !$this->first_name) {
            $this->addError('business_name', 'يجب إدخال اسم الشركة أو الاسم الشخصي على الأقل');
            return;
        }

        $data = [
            'business_name'   => $this->business_name   ?: null,
            'first_name'      => $this->first_name       ?: null,
            'last_name'       => $this->last_name        ?: null,
            'email'           => $this->email            ?: null,
            'phone_primary'   => $this->phone_primary    ?: null,
            'phone_secondary' => $this->phone_secondary  ?: null,
            'city'            => $this->city             ?: null,
            'country_code'    => $this->country_code     ?: null,
            'notes'           => $this->notes            ?: null,
        ];

        if ($this->supplierId) {
            Supplier::findOrFail($this->supplierId)->update($data);
            $msg = 'تم تحديث بيانات المورد';
        } else {
            Supplier::create($data);
            $msg = 'تم إضافة المورد بنجاح';
        }

        session()->flash('toast', $msg);
        $this->redirect(route('suppliers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.supplier-form');
    }
}
