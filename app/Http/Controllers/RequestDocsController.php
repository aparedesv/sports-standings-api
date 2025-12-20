<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Rakutentech\LaravelRequestDocs\Controllers\LaravelRequestDocsController;
use Rakutentech\LaravelRequestDocs\LaravelRequestDocs;
use Rakutentech\LaravelRequestDocs\LaravelRequestDocsToOpenApi;

class RequestDocsController extends LaravelRequestDocsController
{
    private LaravelRequestDocs $laravelRequestDocs;
    private LaravelRequestDocsToOpenApi $laravelRequestDocsToOpenApi;

    public function __construct(LaravelRequestDocs $laravelRequestDoc, LaravelRequestDocsToOpenApi $laravelRequestDocsToOpenApi)
    {
        parent::__construct($laravelRequestDoc, $laravelRequestDocsToOpenApi);
        $this->laravelRequestDocsToOpenApi = $laravelRequestDocsToOpenApi;
        $this->laravelRequestDocs = $laravelRequestDoc;
    }

    /**
     * Override to apply exclude_http_methods config to API view.
     */
    public function api(Request $request): JsonResponse
    {
        $excludedMethods = array_map(
            'strtolower',
            config('request-docs.open_api.exclude_http_methods', [])
        );

        $showGet = !in_array('get', $excludedMethods)
            && (!$request->has('showGet') || $request->input('showGet') === 'true');
        $showPost = !in_array('post', $excludedMethods)
            && (!$request->has('showPost') || $request->input('showPost') === 'true');
        $showPut = !in_array('put', $excludedMethods)
            && (!$request->has('showPut') || $request->input('showPut') === 'true');
        $showPatch = !in_array('patch', $excludedMethods)
            && (!$request->has('showPatch') || $request->input('showPatch') === 'true');
        $showDelete = !in_array('delete', $excludedMethods)
            && (!$request->has('showDelete') || $request->input('showDelete') === 'true');
        $showHead = !in_array('head', $excludedMethods)
            && (!$request->has('showHead') || $request->input('showHead') === 'true');

        $docs = $this->laravelRequestDocs->getDocs(
            $showGet,
            $showPost,
            $showPut,
            $showPatch,
            $showDelete,
            $showHead,
        );

        $docs = $this->laravelRequestDocs->splitByMethods($docs);
        $docs = $this->laravelRequestDocs->sortDocs($docs, $request->input('sort'));
        $docs = $this->laravelRequestDocs->groupDocs($docs, $request->input('groupby'));

        if ($request->input('openapi')) {
            return response()->json(
                $this->laravelRequestDocsToOpenApi->openApi($docs->all())->toArray(),
                Response::HTTP_OK,
                ['Content-type' => 'application/json; charset=utf-8'],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
        }

        return response()->json(
            $docs,
            Response::HTTP_OK,
            ['Content-type' => 'application/json; charset=utf-8'],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}
