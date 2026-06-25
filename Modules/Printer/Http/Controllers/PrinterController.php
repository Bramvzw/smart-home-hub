<?php

namespace Modules\Printer\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Printer\Http\Resources\FilamentSpoolResource;
use Modules\Printer\Http\Resources\PrinterPartResource;
use Modules\Printer\View\ViewModels\PrintingViewModel;

class PrinterController
{
    public function index(Request $request, PrintingViewModel $viewModel): View|JsonResponse
    {
        $filament = $viewModel->filament();
        $parts = $viewModel->parts();

        if ($request->wantsJson()) {
            return response()->json([
                'filament' => FilamentSpoolResource::collection($filament),
                'parts' => PrinterPartResource::collection($parts),
            ]);
        }

        return view('printer::index', [
            'filament' => $filament,
            'parts' => $parts,
        ]);
    }
}
