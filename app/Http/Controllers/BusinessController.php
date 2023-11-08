<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRegisterBusinessRequest;
use App\Http\Requests\BusinessCreateRequest;

use App\Http\Requests\BusinessUpdateRequest;
use App\Http\Requests\BusinessUpdateSeparateRequest;
use App\Http\Requests\ImageUploadRequest;
use App\Http\Requests\MultipleImageUploadRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\UserActivityUtil;
use App\Mail\SendPassword;

use App\Models\Business;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;


class BusinessController extends Controller
{
    use ErrorUtil,BusinessUtil,UserActivityUtil;


       /**
        *
     * @OA\Post(
     *      path="/v1.0/business-image",
     *      operationId="createBusinessImage",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store business image ",
     *      description="This method is to store business image",
     *
   *  @OA\RequestBody(
        *   * @OA\MediaType(
*     mediaType="multipart/form-data",
*     @OA\Schema(
*         required={"image"},
*         @OA\Property(
*             description="image to upload",
*             property="image",
*             type="file",
*             collectionFormat="multi",
*         )
*     )
* )



     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function createBusinessImage(ImageUploadRequest $request)
    {
        try{
            $this->storeActivity($request,"");
            // if(!$request->user()->hasPermissionTo('business_create')){
            //      return response()->json([
            //         "message" => "You can not perform this action"
            //      ],401);
            // }

            $insertableData = $request->validated();

            $location =  config("setup-config.business_gallery_location");

            $new_file_name = time() . '_' . str_replace(' ', '_', $insertableData["image"]->getClientOriginalName());

            $insertableData["image"]->move(public_path($location), $new_file_name);


            return response()->json(["image" => $new_file_name,"location" => $location,"full_location"=>("/".$location."/".$new_file_name)], 200);


        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }

  /**
        *
     * @OA\Post(
     *      path="/v1.0/business-image-multiple",
     *      operationId="createBusinessImageMultiple",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to store business gallery",
     *      description="This method is to store business gallery",
     *
   *  @OA\RequestBody(
        *   * @OA\MediaType(
*     mediaType="multipart/form-data",
*     @OA\Schema(
*         required={"images[]"},
*         @OA\Property(
*             description="array of images to upload",
*             property="images[]",
*             type="array",
*             @OA\Items(
*                 type="file"
*             ),
*             collectionFormat="multi",
*         )
*     )
* )



     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function createBusinessImageMultiple(MultipleImageUploadRequest $request)
    {
        try{
            $this->storeActivity($request,"");

            $insertableData = $request->validated();

            $location =  config("setup-config.business_gallery_location");

            $images = [];
            if(!empty($insertableData["images"])) {
                foreach($insertableData["images"] as $image){
                    $new_file_name = time() . '_' . str_replace(' ', '_', $image->getClientOriginalName());
                    $image->move(public_path($location), $new_file_name);

                    array_push($images,("/".$location."/".$new_file_name));


                    // BusinessGallery::create([
                    //     "image" => ("/".$location."/".$new_file_name),
                    //     "business_id" => $business_id
                    // ]);

                }
            }


            return response()->json(["images" => $images], 201);


        } catch(Exception $e){
            error_log($e->getMessage());
        return $this->sendError($e,500,$request);
        }
    }

    /**
        *
     * @OA\Post(
     *      path="/v1.0/businesses",
     *      operationId="createBusiness",
     *      tags={"business_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store business",
     *      description="This method is to store  business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},

     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *  "owner_id":"1",
     * "name":"ABCD businesses",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  * "currency":"BDT",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",

     *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"}
     *
     * }),
     *
     *


     *
     *

     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */
    public function createBusiness(BusinessCreateRequest $request) {

        try{
            $this->storeActivity($request,"");
     return  DB::transaction(function ()use (&$request) {

        if(!$request->user()->hasPermissionTo('business_create')){
            return response()->json([
               "message" => "You can not perform this action"
            ],401);
       }
        $insertableData = $request->validated();



$user = User::where([
    "id" =>  $insertableData['business']['owner_id']
])
->first();

if(!$user) {
    $error =  [
        "message" => "The given data was invalid.",
        "errors" => ["owner_id"=>["No User Found"]]
 ];
    throw new Exception(json_encode($error),422);
}

if(!$user->hasRole('business_owner')) {
    $error =  [
        "message" => "The given data was invalid.",
        "errors" => ["owner_id"=>["The user is not a businesses Owner"]]
 ];
    throw new Exception(json_encode($error),422);
}



        $insertableData['business']['status'] = "pending";

        $insertableData['business']['created_by'] = $request->user()->id;
        $insertableData['business']['is_active'] = true;
        $business =  Business::create($insertableData['business']);












        return response([

            "business" => $business
        ], 201);
        });
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }


     /**
        *
     * @OA\Post(
     *      path="/v1.0/auth/register-with-business",
     *      operationId="registerUserWithBusiness",
     *      tags={"business_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user with business",
     *      description="This method is to store user with business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},
     *             @OA\Property(property="user", type="string", format="array",example={
     * "first_Name":"Rifat",
     * "last_Name":"Al-Ashwad",
     * "email":"rifatalashwad@gmail.com",
     *  "password":"12345678",
     *  "password_confirmation":"12345678",
     *  "phone":"01771034383",
     *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     * "send_password":1
     *
     *
     * }),
     *
     *  @OA\Property(property="business", type="string", format="array",example={
     * "name":"ABCD businesses",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  * "currency":"BDT",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",

     *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"}
     *
     * }),
     *

     *
     *

     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */
    public function registerUserWithBusiness(AuthRegisterBusinessRequest $request) {

        try{
            $this->storeActivity($request,"");
     return  DB::transaction(function ()use (&$request) {

        if(!$request->user()->hasPermissionTo('business_create')){
            return response()->json([
               "message" => "You can not perform this action"
            ],401);
       }
        $insertableData = $request->validated();

   // user info starts ##############

   $password = $insertableData['user']['password'];
   $insertableData['user']['password'] = Hash::make($password);
   if(!$request->user()->hasRole('superadmin') || empty($insertableData['user']['password'])) {
    $password = Str::random(10);
    $insertableData['user']['password'] = Hash::make($password);
    }




    $insertableData['user']['remember_token'] = Str::random(10);
    $insertableData['user']['is_active'] = true;
    $insertableData['user']['created_by'] = $request->user()->id;

    $insertableData['user']['address_line_1'] = $insertableData['business']['address_line_1'];
    $insertableData['user']['address_line_2'] = (!empty($insertableData['business']['address_line_2'])?$insertableData['business']['address_line_2']:"") ;
    $insertableData['user']['country'] = $insertableData['business']['country'];
    $insertableData['user']['city'] = $insertableData['business']['city'];
    $insertableData['user']['postcode'] = $insertableData['business']['postcode'];
    $insertableData['user']['lat'] = $insertableData['business']['lat'];
    $insertableData['user']['long'] = $insertableData['business']['long'];

    $user =  User::create($insertableData['user']);
    $user->email_verified_at = now();
    $user->save();

    $user->assignRole('business_owner');
   // end user info ##############


  //  business info ##############


        $insertableData['business']['status'] = "pending";
        $insertableData['business']['owner_id'] = $user->id;
        $insertableData['business']['created_by'] = $request->user()->id;
        $insertableData['business']['is_active'] = true;
        $business =  Business::create($insertableData['business']);







  // end business info ##############


     if($insertableData['user']['send_password']) {
        if(env("SEND_EMAIL") == true) {
            Mail::to($insertableData['user']['email'])->send(new SendPassword($user,$password));
        }
    }

        return response([
            "user" => $user,
            "business" => $business
        ], 201);
        });
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }



     /**
        *
     * @OA\Put(
     *      path="/v1.0/businesses",
     *      operationId="updateBusiness",
     *      tags={"business_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user with business",
     *      description="This method is to update user with business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},
     *             @OA\Property(property="user", type="string", format="array",example={
     *  * "id":1,
     * "first_Name":"Rifat",
     * "last_Name":"Al-Ashwad",
     * "email":"rifatalashwad@gmail.com",
     *  "password":"12345678",
     *  "password_confirmation":"12345678",
     *  "phone":"01771034383",
     *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *
     *
     * }),
     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *   *  * "id":1,
     * "name":"ABCD businesses",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *      *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"},
     *  "currency":"BDT"
     *
     * }),
     *

     *
     *

     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */
    public function updateBusiness(BusinessUpdateRequest $request) {

        try{
            $this->storeActivity($request,"");
     return  DB::transaction(function ()use (&$request) {
        if(!$request->user()->hasPermissionTo('business_update')){
            return response()->json([
               "message" => "You can not perform this action"
            ],401);
       }
       if (!$this->businessOwnerCheck($request["business"]["id"])) {
        return response()->json([
            "message" => "you are not the owner of the business or the requested business does not exist."
        ], 401);
    }

       $updatableData = $request->validated();
    //    user email check
       $userPrev = User::where([
        "id" => $updatableData["user"]["id"]
       ]);
       if(!$request->user()->hasRole('superadmin')) {
        $userPrev  = $userPrev->where(function ($query) {
            $query->where('created_by', auth()->user()->id)
                  ->orWhere('id', auth()->user()->id);
        });
    }
    $userPrev = $userPrev->first();
     if(!$userPrev) {
            return response()->json([
               "message" => "no user found with this id"
            ],404);
     }




    //  $businessPrev = Business::where([
    //     "id" => $updatableData["business"]["id"]
    //  ]);

    // $businessPrev = $businessPrev->first();
    // if(!$businessPrev) {
    //     return response()->json([
    //        "message" => "no business found with this id"
    //     ],404);
    //   }

        if(!empty($updatableData['user']['password'])) {
            $updatableData['user']['password'] = Hash::make($updatableData['user']['password']);
        } else {
            unset($updatableData['user']['password']);
        }
        $updatableData['user']['is_active'] = true;
        $updatableData['user']['remember_token'] = Str::random(10);
        $updatableData['user']['address_line_1'] = $updatableData['business']['address_line_1'];
    $updatableData['user']['address_line_2'] = $updatableData['business']['address_line_2'];
    $updatableData['user']['country'] = $updatableData['business']['country'];
    $updatableData['user']['city'] = $updatableData['business']['city'];
    $updatableData['user']['postcode'] = $updatableData['business']['postcode'];
    $updatableData['user']['lat'] = $updatableData['business']['lat'];
    $updatableData['user']['long'] = $updatableData['business']['long'];
        $user  =  tap(User::where([
            "id" => $updatableData['user']["id"]
            ]))->update(collect($updatableData['user'])->only([
            'first_Name',
            'last_Name',
            'phone',
            'image',
            'address_line_1',
            'address_line_2',
            'country',
            'city',
            'postcode',
            'email',
            'password',
            "lat",
            "long",
        ])->toArray()
        )
            // ->with("somthing")

            ->first();
            if(!$user) {
                return response()->json([
                    "message" => "no user found"
                    ],404);

        }

        $user->syncRoles(["business_owner"]);



  //  business info ##############
        // $updatableData['business']['status'] = "pending";

        $business  =  tap(Business::where([
            "id" => $updatableData['business']["id"]
            ]))->update(collect($updatableData['business'])->only([
                "name",
                "about",
                "web_page",
                "phone",
                "email",
                "additional_information",
                "address_line_1",
                "address_line_2",
                "lat",
                "long",
                "country",
                "city",
                "postcode",
                "logo",
                "image",
                "status",
                // "is_active",




                "currency",

        ])->toArray()
        )
            // ->with("somthing")

            ->first();
            if(!$business) {
                return response()->json([
                    "massage" => "no business found"
                ],404);

            }


  // end business info ##############






        return response([
            "user" => $user,
            "business" => $business
        ], 201);
        });
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }



     /**
        *
     * @OA\Put(
     *      path="/v1.0/businesses/toggle-active",
     *      operationId="toggleActiveBusiness",
     *      tags={"business_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle business",
     *      description="This method is to toggle business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *           @OA\Property(property="id", type="string", format="number",example="1"),
     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

     public function toggleActiveBusiness(GetIdRequest $request)
     {

         try{
             $this->storeActivity($request,"");
             if(!$request->user()->hasPermissionTo('business_update')){
                 return response()->json([
                    "message" => "You can not perform this action"
                 ],401);
            }
            $updatableData = $request->validated();

            $businessQuery  = Business::where(["id" => $updatableData["id"]]);
            if(!auth()->user()->hasRole('superadmin')) {
                $businessQuery = $businessQuery->where(function ($query) {
                    $query->where('created_by', auth()->user()->id);
                });
            }

            $business =  $businessQuery->first();


            if (!$business) {
                return response()->json([
                    "message" => "no business found"
                ], 404);
            }


            $business->update([
                'is_active' => !$business->is_active
            ]);

            return response()->json(['message' => 'business status updated successfully'], 200);


         } catch(Exception $e){
             error_log($e->getMessage());
         return $this->sendError($e,500,$request);
         }
     }





      /**
        *
     * @OA\Put(
     *      path="/v1.0/businesses/separate",
     *      operationId="updateBusinessSeparate",
     *      tags={"business_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update business",
     *      description="This method is to update business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"business"},

     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *   *  * "id":1,
     * "name":"ABCD businesses",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *      *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"},
     * *  "currency":"BDT"
     *
     * }),
     *

     *

     *
     *         ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */
    public function updateBusinessSeparate(BusinessUpdateSeparateRequest $request) {

        try{
            $this->storeActivity($request,"");
     return  DB::transaction(function ()use (&$request) {
        if(!$request->user()->hasPermissionTo('business_update')){
            return response()->json([
               "message" => "You can not perform this action"
            ],401);
       }
       if (!$this->businessOwnerCheck($request["business"]["id"])) {
        return response()->json([
            "message" => "you are not the owner of the business or the requested business does not exist."
        ], 401);
    }

       $updatableData = $request->validated();


  //  business info ##############
        // $updatableData['business']['status'] = "pending";

        $business  =  tap(Business::where([
            "id" => $updatableData['business']["id"]
            ]))->update(collect($updatableData['business'])->only([
                "name",
                "about",
                "web_page",
                "phone",
                "email",
                "additional_information",
                "address_line_1",
                "address_line_2",
                "lat",
                "long",
                "country",
                "city",
                "postcode",
                "logo",
                "image",
                "status",
                // "is_active",



             "currency",

        ])->toArray()
        )
            // ->with("somthing")

            ->first();
            if(!$business) {
                return response()->json([
                    "massage" => "no business found"
                ],404);

            }








        return response([
            "business" => $business
        ], 201);
        });
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }



    /**
        *
     * @OA\Get(
     *      path="/v1.0/businesses/{perPage}",
     *      operationId="getBusinesses",
     *      tags={"business_management"},
     * *  @OA\Parameter(
* name="start_date",
* in="query",
* description="start_date",
* required=true,
* example="2019-06-29"
* ),
     * *  @OA\Parameter(
* name="end_date",
* in="query",
* description="end_date",
* required=true,
* example="2019-06-29"
* ),
     * *  @OA\Parameter(
* name="search_key",
* in="query",
* description="search_key",
* required=true,
* example="search_key"
* ),
     * *  @OA\Parameter(
* name="country_code",
* in="query",
* description="country_code",
* required=true,
* example="country_code"
* ),
    * *  @OA\Parameter(
* name="address",
* in="query",
* description="address",
* required=true,
* example="address"
* ),
     * *  @OA\Parameter(
* name="city",
* in="query",
* description="city",
* required=true,
* example="city"
* ),
    * *  @OA\Parameter(
* name="start_lat",
* in="query",
* description="start_lat",
* required=true,
* example="3"
* ),
     * *  @OA\Parameter(
* name="end_lat",
* in="query",
* description="end_lat",
* required=true,
* example="2"
* ),
     * *  @OA\Parameter(
* name="start_long",
* in="query",
* description="start_long",
* required=true,
* example="1"
* ),
     * *  @OA\Parameter(
* name="end_long",
* in="query",
* description="end_long",
* required=true,
* example="4"
* ),
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="perPage",
     *         in="path",
     *         description="perPage",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get businesses",
     *      description="This method is to get businesses",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinesses($perPage,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('business_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            $businessesQuery = Business::with(
                "owner",


            );


            if(!$request->user()->hasRole('superadmin')) {
                $businessesQuery = $businessesQuery->where(function ($query) use ($request) {
                    $query->where('created_by', $request->user()->id)
                          ->orWhere('owner_id', $request->user()->id);
                });
            }

            if(!empty($request->search_key)) {
                $businessesQuery = $businessesQuery->where(function($query) use ($request){
                    $term = $request->search_key;
                    $query->where("name", "like", "%" . $term . "%");
                    $query->orWhere("phone", "like", "%" . $term . "%");
                    $query->orWhere("email", "like", "%" . $term . "%");
                    $query->orWhere("city", "like", "%" . $term . "%");
                    $query->orWhere("postcode", "like", "%" . $term . "%");
                });

            }


            if (!empty($request->start_date)) {
                $businessesQuery = $businessesQuery->where('created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $businessesQuery = $businessesQuery->where('created_at', "<=", $request->end_date);
            }

            if (!empty($request->start_lat)) {
                $businessesQuery = $businessesQuery->where('lat', ">=", $request->start_lat);
            }
            if (!empty($request->end_lat)) {
                $businessesQuery = $businessesQuery->where('lat', "<=", $request->end_lat);
            }
            if (!empty($request->start_long)) {
                $businessesQuery = $businessesQuery->where('long', ">=", $request->start_long);
            }
            if (!empty($request->end_long)) {
                $businessesQuery = $businessesQuery->where('long', "<=", $request->end_long);
            }

            if (!empty($request->address)) {
                $businessesQuery = $businessesQuery->where(function ($query) use ($request) {
                    $term = $request->address;
                    $query->where("country", "like", "%" . $term . "%");
                    $query->orWhere("city", "like", "%" . $term . "%");


                });
            }
            if (!empty($request->country_code)) {
                $businessesQuery =   $businessesQuery->orWhere("country", "like", "%" . $request->country_code . "%");

            }
            if (!empty($request->city)) {
                $businessesQuery =   $businessesQuery->orWhere("city", "like", "%" . $request->city . "%");

            }


            $businesses = $businessesQuery->orderByDesc("id")->paginate($perPage);
            return response()->json($businesses, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

     /**
        *
     * @OA\Get(
     *      path="/v1.0/businesses/single/{id}",
     *      operationId="getBusinessById",
     *      tags={"business_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to get business by id",
     *      description="This method is to get business by id",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getBusinessById($id,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('business_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           if (!$this->businessOwnerCheck($id)) {
            return response()->json([
                "message" => "you are not the owner of the business or the requested business does not exist."
            ], 401);
        }

            $business = Business::with(
                "owner",

            )->where([
                "id" => $id
            ])
            ->first();


        $data["business"] = $business;

        return response()->json($data, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

/**
        *
     * @OA\Delete(
     *      path="/v1.0/businesses/{id}",
     *      operationId="deleteBusinessById",
     *      tags={"business_management"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to delete business by id",
     *      description="This method is to delete business by id",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function deleteBusinessById($id,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('business_delete')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

           $businessesQuery =   Business::where([
            "id" => $id
           ]);
           if(!$request->user()->hasRole('superadmin')) {
            $businessesQuery =    $businessesQuery->where([
                "created_by" =>$request->user()->id
            ]);
        }

        $business = $businessesQuery->first();

        $business->delete();



            return response()->json(["ok" => true], 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }



    }







    /**
        *
     * @OA\Get(
     *      path="/v1.0/businesses/by-business-owner/all",
     *      operationId="getAllBusinessesByBusinessOwner",
     *      tags={"business_management"},

    *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to get businesses",
     *      description="This method is to get businesses",
     *

     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       @OA\JsonContent(),
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     * @OA\JsonContent(),
     *      ),
     *        @OA\Response(
     *          response=422,
     *          description="Unprocesseble Content",
     *    @OA\JsonContent(),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *   @OA\JsonContent()
     * ),
     *  * @OA\Response(
     *      response=400,
     *      description="Bad Request",
     *   *@OA\JsonContent()
     *   ),
     * @OA\Response(
     *      response=404,
     *      description="not found",
     *   *@OA\JsonContent()
     *   )
     *      )
     *     )
     */

    public function getAllBusinessesByBusinessOwner(Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasRole('business_owner')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

            $businessesQuery = Business::where([
                "owner_id" => $request->user()->id
            ]);



            $businesses = $businessesQuery->orderByDesc("id")->get();
            return response()->json($businesses, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }


}
