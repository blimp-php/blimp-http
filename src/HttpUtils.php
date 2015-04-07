<?php
namespace Blimp\Http;

class HttpUtils {
    protected $api;

    public function __construct($api) {
        $this->api = $api;
    }

    public function guessContentLang($languages) {
        if(!empty($languages)) {
            foreach ($languages as $lang) {
                $lang = str_replace('_', '-', $lang);
                // TODO get it from somewhere
                if(in_array($lang, ['pt-PT', 'en-US', 'en'])) {
                    return $lang;
                }
            }

            if(empty($contentLang)) {
                throw new BlimpHttpException(Response::HTTP_NOT_ACCEPTABLE, 'Requested language not supported', ["requested" => $request->headers->get('Accept-Language'), "available" => ['pt-PT', 'en-US', 'en']]);
            }
        } else {
            // TODO get it from somewhere
            return 'pt-PT';
        }
    }
}
