<?php

namespace Modules\Printer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Printer\Actions\Parts\AdjustPartQuantity;
use Modules\Printer\Actions\Parts\CreatePart;
use Modules\Printer\Actions\Parts\DeletePart;
use Modules\Printer\Actions\Parts\UpdatePart;
use Modules\Printer\Http\Requests\AdjustPartRequest;
use Modules\Printer\Http\Requests\StorePartRequest;
use Modules\Printer\Http\Requests\UpdatePartRequest;
use Modules\Printer\Http\Resources\PrinterPartResource;
use Modules\Printer\Models\PrinterPart;

class PrinterPartController
{
    public function store(StorePartRequest $request, CreatePart $createPart): JsonResponse
    {
        $part = $createPart($request->validated());

        return PrinterPartResource::make($part)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdatePartRequest $request, PrinterPart $part, UpdatePart $updatePart): PrinterPartResource
    {
        return PrinterPartResource::make($updatePart($part, $request->validated()));
    }

    public function destroy(PrinterPart $part, DeletePart $deletePart): Response
    {
        $deletePart($part);

        return response()->noContent();
    }

    public function adjust(AdjustPartRequest $request, PrinterPart $part, AdjustPartQuantity $adjust): PrinterPartResource
    {
        return PrinterPartResource::make($adjust($part, (float) $request->validated('delta')));
    }
}
