<?php

namespace L5Swagger\Http\Controllers;

use File;
use Request;
use Response;
use L5Swagger\Generator;
use Illuminate\Routing\Controller as BaseController;

class SwaggerController extends BaseController
{
    private $packageSwaggerConf;

    public function __construct()
    {
        $currentPath = Request::path();
        $packagesWithDocs = config('swagger');
        if($packagesWithDocs) {
            foreach($packagesWithDocs as $package => $conf) {
                if($currentPath == $conf['routes']['api'] || $conf['routes']['docs'].'/'.$conf['paths']['docs_json']   ) {
                    $this->packageSwaggerConf = $conf;
                }
            }
        }
    }

    /**
     * Dump api-docs.json content endpoint.
     *
     * @param string $jsonFile
     *
     * @return \Response
     */
    public function docs($jsonFile = null)
    {
        // first check if pre-generated file exists
        $filePath = $this->packageSwaggerConf['paths']['annotations'] . '/Docs/' .
            $this->packageSwaggerConf['paths']['docs_json'];

        // else try to use generated docs
        if( ! File::exists($filePath)) {
            $filePath = config('l5-swagger.paths.docs').'/'.
                (! is_null($jsonFile) ? $jsonFile : config('l5-swagger.paths.docs_json', 'api-docs.json'));

            if (! File::exists($filePath)) {
                abort(404, 'Cannot find '.$filePath);
            }
        }

        $content = File::get($filePath);

        return Response::make($content, 200, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Display Swagger API page.
     *
     * @return \Response
     */
    public function api()
    {
        if (config('l5-swagger.generate_always')) {
            $packagesWithDocs = config('swagger');
            if($packagesWithDocs) {
                foreach($packagesWithDocs as $package => $conf) {
                    $this->info('Regenerating docs for: ' . $package);
                    Generator::generateDocs($conf);
                }
            }
        }

        if ($proxy = config('l5-swagger.proxy')) {
            if (! is_array($proxy)) {
                $proxy = [$proxy];
            }
            Request::setTrustedProxies($proxy, \Illuminate\Http\Request::HEADER_X_FORWARDED_ALL);
        }

        // Need the / at the end to avoid CORS errors on Homestead systems.
        $response = Response::make(
            view('l5-swagger::index', [
                'secure' => Request::secure(),
                'urlToDocs' => route('l5-swagger.docs', $this->packageSwaggerConf['paths']['docs_json']),
                'operationsSorter' => config('l5-swagger.operations_sort'),
                'configUrl' => config('l5-swagger.additional_config_url'),
                'validatorUrl' => config('l5-swagger.validator_url'),
            ]),
            200
        );

        return $response;
    }

    /**
     * Display Oauth2 callback pages.
     *
     * @return string
     * @throws \L5Swagger\Exceptions\L5SwaggerException
     */
    public function oauth2Callback()
    {
        return \File::get(swagger_ui_dist_path('oauth2-redirect.html'));
    }
}
