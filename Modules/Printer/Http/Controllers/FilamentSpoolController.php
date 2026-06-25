<?php

namespace Modules\Printer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Printer\Actions\Filament\AdjustSpoolRemaining;
use Modules\Printer\Actions\Filament\CreateSpool;
use Modules\Printer\Actions\Filament\DeleteSpool;
use Modules\Printer\Actions\Filament\UpdateSpool;
use Modules\Printer\Http\Requests\AdjustSpoolRequest;
use Modules\Printer\Http\Requests\StoreSpoolRequest;
use Modules\Printer\Http\Requests\UpdateSpoolRequest;
use Modules\Printer\Http\Resources\FilamentSpoolResource;
use Modules\Printer\Models\FilamentSpool;

class FilamentSpoolController
{
    public function store(StoreSpoolRequest $request, CreateSpool $createSpool): JsonResponse
    {
        $spool = $createSpool($request->validated());

        return FilamentSpoolResource::make($spool)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateSpoolRequest $request, FilamentSpool $spool, UpdateSpool $updateSpool): FilamentSpoolResource
    {
        return FilamentSpoolResource::make($updateSpool($spool, $request->validated()));
    }

    public function destroy(FilamentSpool $spool, DeleteSpool $deleteSpool): Response
    {
        $deleteSpool($spool);

        return response()->noContent();
    }

    public function adjust(AdjustSpoolRequest $request, FilamentSpool $spool, AdjustSpoolRemaining $adjust): FilamentSpoolResource
    {
        return FilamentSpoolResource::make($adjust($spool, (int) $request->validated('delta_g')));
    }
}
