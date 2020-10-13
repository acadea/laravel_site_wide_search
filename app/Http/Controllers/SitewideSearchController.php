<?php

namespace App\Http\Controllers;

use App\Http\Resources\SiteSearchResource;
use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class SitewideSearchController extends Controller
{
    const BUFFER = 10;  // 10 characters: to show 10 neighbouring characters around the searched word
    private function modelNamespacePrefix()
    {
        return app()->getNamespace() . 'Models\\';
    }

    public function search(Request $request)
    {
        $keyword = $request->search;

        $toExclude = [];

        $files = File::allFiles(app()->basePath() . '/app/Models');

        // to get all the model classes
        $results = collect($files)->map(function (SplFileInfo $file){
            $filename = $file->getRelativePathname();

            // assume model name is equal to file name
            /* making sure it is a php file*/
            if (substr($filename, -4) !== '.php'){
                return null;
            }
            // removing .php
            return substr($filename, 0, -4);

        })->filter(function (?string $classname){
            if($classname === null){
                return false;
            }

            // using reflection class to obtain class info dynamically
            $reflection = new \ReflectionClass($this->modelNamespacePrefix() . $classname);

            // making sure the class extended eloquent model
            $isModel = $reflection->isSubclassOf(Model::class);

            // making sure the model implemented the searchable trait
            $searchable = $reflection->hasMethod('search');
            // filter model that has the searchable trait
            return $isModel && $searchable;

        })->map(function ($classname) use ($keyword) {
            // for each class, call the search function
            $model = app($this->modelNamespacePrefix() . $classname);

            // assume there is a resource class following the convention
//            /** @var JsonResource $resourceClass*/
//            $resourceClass = '\\App\\Http\\Resources\\' . $classname . 'Resource';
            // using a standardised site search resource
            $resourceCollection = SiteSearchResource::collection($model::search($keyword)->get());

            return $resourceCollection->collection->map(function ($modelRecord) use ($model, $keyword, $classname){
                $fields = array_filter($model::SEARCHABLE_FIELDS, fn($field) => $field !== 'id');

                $fieldsData = $modelRecord->resource->only($fields);

                $serializedValues = collect($fieldsData)->join('');

                $searchPos = strpos(strtolower($serializedValues), strtolower($keyword));
                // including the found terms
                if($searchPos !== false){
                    // buffer of +- 10 characters
                    $start = $searchPos - self::BUFFER;
                    $start = $start < 0 ? 0 : $start;
                    $length = strlen($keyword) + 2 * self::BUFFER;

                    $sliced = substr($serializedValues, $start, $length);
                    // adding prefix
                    $shouldAddPrefix = $start > 0;
                    $shouldAddPostfix = ($start + $length) < strlen($serializedValues) ;

                    $sliced =  $shouldAddPrefix ? '...' . $sliced : $sliced;
                    // adding end dots
                    $sliced = $shouldAddPostfix ? $sliced . '...' : $sliced;

                }
                $modelRecord->setAttribute('searched', $sliced ?? $serializedValues);
                $modelRecord->setAttribute('model', $classname);
                $modelRecord->setAttribute('view_link', $this->resolveModelViewLink($modelRecord->resource));
                return $modelRecord;

            });
        })->flatten(1);

        return new JsonResponse([
            'data' => $results,
        ]);

    }

    private function resolveModelViewLink(Model $model)
    {
        $map = [

        ];
        // converting model name to kebab case
        $modelName = Str::plural(Arr::last(explode('\\', get_class($model))));

        $modelName = Str::kebab(Str::camel($modelName));


        // assume /{model-name}/{model_id}
        return URL::to('/' . strtolower($modelName) . '/' . $model->id);
    }
}
