<?php

namespace App\Http\Controllers;

use App\Exports\CompanyPhonesExport;
use App\Models\CompanyPhone;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CompanyPhoneController extends Controller
{
    public function publicForm()
    {
        return Inertia::render('Public/CompanyPhonePublicForm');
    }

    public function publicStore(Request $request): JsonResponse
    {
        if ($request->filled('website')) {
            return response()->json(['success' => false, 'errors' => ['Invalid submission']], 422);
        }

        $validator = Validator::make($request->all(), [
            'department' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'person_in_charge' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'extension' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $item = CompanyPhone::create($validator->validated());

        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function page()
    {
        return Inertia::render('CompanyPhones/CompanyPhoneList');
    }

    public function importPage()
    {
        return Inertia::render('CompanyPhones/CompanyPhoneImport');
    }

    public function formPage(?CompanyPhone $companyPhone = null)
    {
        return Inertia::render('CompanyPhones/CompanyPhoneForm', [
            'phoneRecord' => $companyPhone,
        ]);
    }

    public function showPage(CompanyPhone $companyPhone)
    {
        return Inertia::render('CompanyPhones/Show', [
            'phone' => [
                'id' => $companyPhone->id,
                'department' => $companyPhone->department,
                'phone_number' => $companyPhone->phone_number,
                'person_in_charge' => $companyPhone->person_in_charge,
                'position' => $companyPhone->position,
                'extension' => $companyPhone->extension,
                'created_at' => optional($companyPhone->created_at)->toDateTimeString(),
                'updated_at' => optional($companyPhone->updated_at)->toDateTimeString(),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $search = (string) $request->get('search', '');
        $department = (string) $request->get('department', '');
        $perPage = (int) $request->get('perPage', 10);
        $allowed = [10, 25, 50, 100];
        if (! in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $query = CompanyPhone::query();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                    ->orWhere('person_in_charge', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('extension', 'like', "%{$search}%");
            });
        }
        if ($department !== '') {
            $query->where('department', 'like', "%{$department}%");
        }

        $paginator = $query->orderBy('department')->orderBy('phone_number')->paginate($perPage)->appends($request->query());

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'department' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:32', 'regex:/^[+0-9()\-\s]{6,32}$/', 'unique:company_phones,phone_number'],
            'person_in_charge' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'extension' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $item = CompanyPhone::create($validator->validated());

        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function update(Request $request, CompanyPhone $companyPhone): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'department' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'regex:/^\+639\d{10}$/', 'unique:company_phones,phone_number,'.$companyPhone->id],
            'person_in_charge' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'extension' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $companyPhone->update($validator->validated());

        return response()->json(['success' => true, 'data' => $companyPhone]);
    }

    public function destroy(CompanyPhone $companyPhone): JsonResponse
    {
        $companyPhone->delete();

        return response()->json(['success' => true]);
    }

    public function importExcel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $rows = Excel::toArray(null, $request->file('file'))[0] ?? [];
        foreach (array_slice($rows, 1) as $row) {
            $department = $row[0] ?? null;
            $phone = $row[1] ?? null;
            $person = $row[2] ?? null;
            $position = $row[3] ?? null;
            $extension = $row[4] ?? null;
            if (! $phone || ! $department || ! $person || ! $position) {
                continue;
            }
            if (! preg_match('/^\+639\d{10}$/', (string) $phone)) {
                continue;
            }
            CompanyPhone::updateOrCreate(
                ['phone_number' => (string) $phone],
                [
                    'department' => (string) $department,
                    'person_in_charge' => (string) $person,
                    'position' => (string) $position,
                    'extension' => $extension ? (string) $extension : null,
                ]
            );
        }

        return response()->json(['success' => true]);
    }

    public function exportExcel()
    {
        $year = now()->format('Y');
        $createdAt = now()->format('Y-m-d_His');
        $fileName = "CompanyPhones_{$year}_{$createdAt}.xlsx";

        return Excel::download(new CompanyPhonesExport, $fileName);
    }

    public function exportPDF()
    {
        $items = CompanyPhone::orderBy('department')->orderBy('phone_number')->get();
        $pdf = Pdf::loadView('company_phones.pdf', [
            'items' => $items,
        ]);
        $ts = now()->format('Ymd_His');

        return $pdf->download("company_phones_{$ts}.pdf");
    }
}
