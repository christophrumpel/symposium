<?php namespace Symposium\Http\Controllers\Api;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use LucaDegasperi\OAuth2Server\Facades\AuthorizerFacade as Authorizer;
use Symposium\ApiResources\Talk;
use User;

class UserTalksController extends BaseController
{
    /**
     * Display all of the given user's talks
     *
     * @return Response
     */
    public function index($userId)
    {
        if ($userId != Authorizer::getResourceOwnerId()) {
            App::abort(404);
        }

        $talks = User::find($userId)->talks;

        $return = [];

        foreach ($talks as $talk) {
            $resource = new Talk($talk);

            $return[] = [
                'id' => $resource->getId(),
                'type' => $resource->getType(),
                'attributes' => $resource->attributes()
            ];
        }

        return response()->jsonApi([
            'data' => $return
        ]);
    }
}
