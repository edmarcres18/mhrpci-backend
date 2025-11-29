<?php

namespace App\Http\Controllers;

use App\Exports\EmailsExport;
use App\Models\Email;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class EmailController extends Controller
{
    public function publicForm()
    {
        return Inertia::render('Public/EmailPublicForm');
    }

    public function publicStore(Request $request): JsonResponse
    {
        if ($request->filled('website')) {
            return response()->json(['success' => false, 'errors' => ['Invalid submission']], 422);
        }

        $validator = Validator::make($request->all(), [
            'department' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:emails,email'],
            'password' => ['required', 'string', 'max:255'],
            'person_in_charge' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        // Reject sample/test email domains
        $email = strtolower($request->email);
        $domain = substr(strrchr($email, '@'), 1);
        $invalidDomains = ['example.com', 'test.com', 'sample.com', 'demo.com', 'localhost', 'test.test'];
        
        if (in_array($domain, $invalidDomains)) {
            return response()->json(['success' => false, 'errors' => ['Please use a valid company email, not a sample/test email']], 422);
        }

        $data = $validator->validated();
        $data['password'] = Crypt::encryptString($data['password']);

        $item = Email::create($data);

        return response()->json(['success' => true, 'data' => $item], 201);
    }
    public function page()
    {
        return Inertia::render('Emails/EmailList');
    }

    public function importPage()
    {
        return Inertia::render('Emails/EmailImport');
    }

    public function formPage(?Email $email = null)
    {
        if ($email) {
            $password = null;
            try { $password = $email->password ? Crypt::decryptString($email->password) : null; }
            catch (\Throwable $e) { $password = $email->password; }
            return Inertia::render('Emails/EmailForm', [
                'emailRecord' => [
                    'id' => $email->id,
                    'department' => $email->department,
                    'email' => $email->email,
                    'password' => $password,
                    'person_in_charge' => $email->person_in_charge,
                    'position' => $email->position,
                ],
            ]);
        }

        return Inertia::render('Emails/EmailForm', [
            'emailRecord' => null,
        ]);
    }

    public function showPage(Email $email)
    {
        $password = null;
        try {
            $password = $email->password ? Crypt::decryptString($email->password) : null;
        } catch (\Throwable $e) {
            $password = $email->password; // legacy/plain if not encrypted
        }

        return Inertia::render('Emails/Show', [
            'email' => [
                'id' => $email->id,
                'department' => $email->department,
                'email' => $email->email,
                'password' => $password,
                'person_in_charge' => $email->person_in_charge,
                'position' => $email->position,
                'created_at' => optional($email->created_at)->toDateTimeString(),
                'updated_at' => optional($email->updated_at)->toDateTimeString(),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $search = (string) $request->get('search', '');
        $department = (string) $request->get('department', '');
        $perPage = (int) $request->get('perPage', 10);
        $allowed = [10, 25, 50, 100];
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $query = Email::query();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('person_in_charge', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%");
            });
        }
        if ($department !== '') {
            $query->where('department', 'like', "%{$department}%");
        }

        $paginator = $query->orderBy('department')->orderBy('email')->paginate($perPage)->appends($request->query());

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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:emails,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'person_in_charge' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $data = $validator->validated();
        $data['password'] = Crypt::encryptString($data['password']);

        $item = Email::create($data);

        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function update(Request $request, Email $email): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'department' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:emails,email,' . $email->id],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'person_in_charge' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
        }

        $data = $validator->validated();
        if (isset($data['password']) && $data['password'] !== '') {
            $data['password'] = Crypt::encryptString($data['password']);
        } else {
            unset($data['password']);
        }

        $email->update($data);

        return response()->json(['success' => true, 'data' => $email]);
    }

    public function destroy(Email $email): JsonResponse
    {
        $email->delete();
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

        $file = $request->file('file');
        $rows = Excel::toArray(null, $file)[0] ?? [];
        foreach (array_slice($rows, 1) as $row) {
            $department = $row[0] ?? null;
            $email = $row[1] ?? null;
            $password = $row[2] ?? null;
            $person = $row[3] ?? null;
            $position = $row[4] ?? null;
            if (!$email || !$password || !$department || !$person || !$position) {
                continue;
            }
            Email::updateOrCreate(
                ['email' => (string) $email],
                [
                    'department' => (string) $department,
                    'password' => Crypt::encryptString((string) $password),
                    'person_in_charge' => (string) $person,
                    'position' => (string) $position,
                ]
            );
        }

        return response()->json(['success' => true]);
    }

    public function exportExcel()
    {
        $year = now()->format('Y');
        $createdAt = now()->format('Y-m-d_His');
        $fileName = "Emails_{$year}_{$createdAt}.xlsx";
        return Excel::download(new EmailsExport(), $fileName);
    }

    public function exportPDF()
    {
        $items = Email::orderBy('department')->orderBy('email')->get();
        $pdf = Pdf::loadView('emails.pdf', [
            'items' => $items,
        ]);
        $ts = now()->format('Ymd_His');
        return $pdf->download("emails_{$ts}.pdf");
    }
}
