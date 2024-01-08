<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\NewPasswordRequest;
use App\Http\Requests\Auth\OtpVerifyRequest;
use App\Http\Requests\Auth\RecoverOtpVerify;
use App\Http\Requests\Auth\RecoverVerifyRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyReqeust;
use App\Http\Requests\Setting\ChangePasswordRequest;
use App\Http\Requests\Setting\EditProfileRequest;
use App\Http\Requests\Setting\LogoutRequest;
use App\Http\Requests\Setting\RemoveSocialRequest;
use App\Http\Requests\Setting\SocialConnectRequest;
use App\Http\Requests\Setting\SocialLoginRequest;
use App\Mail\OtpSend;
use App\Models\BlockList;
use App\Models\ImageVerify;
use App\Models\OtpVerify;
use App\Models\Social;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function userVerify(VerifyReqeust $request)
    {

        $verify = User::where('username', $request->username)->first();
        if ($verify) {
            return response()->json([
                'status' => false,
                'action' => 'This Username is already exists'
            ]);
        } else {
            $otp = random_int(100000, 999999);

            $mail_details = [
                'body' => $otp,
            ];
            Mail::to($request->email)->send(new OtpSend($mail_details));


            $user = new OtpVerify();
            $user->email = $request->email;
            $user->otp = $otp;
            $user->save();
            return response()->json([
                'status' => true,
                'action' => 'User verify and OTP send',
            ]);
        }
    }

    public function otpVerify(OtpVerifyRequest $request)
    {
        $user = OtpVerify::where('email', $request->email)->latest()->first();
        if ($user) {
            if ($request->otp == $user->otp) {
                $user = OtpVerify::where('email', $request->email)->delete();
                return response()->json([
                    'status' => true,
                    'action' => 'OTP verify',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'action' => 'OTP is invalid, Please enter a valid OTP',
                ]);
            }
        }
    }

    public function register(RegisterRequest $request)
    {

        $user = new User();
        $user->name = $request->full_name;
        $user->email = $request->email;
        $user->username = $request->username;
        $user->name = $request->full_name;
        $user->password = Hash::make($request->password);
        $user->save();
        $userdevice = new UserDevice();
        $userdevice->user_id = $user->id;
        $userdevice->device_name = $request->device_name ?? 'No name';
        $userdevice->device_id = $request->device_id ?? 'No ID';
        $userdevice->timezone = $request->timezone ?? 'No Time';
        $userdevice->token = $request->fcm_token ?? 'No tocken';
        $userdevice->save();



        $newuser  = User::find($user->id);
        $newuser->platform  = 'noraml';

        return response()->json([
            'status' => true,
            'action' => 'User register successfully',
            'data' => $newuser
        ]);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->orWhere('username', $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $userdevice = new UserDevice();
                $userdevice->user_id = $user->id;
                $userdevice->device_name = $request->device_name ?? 'No name';
                $userdevice->device_id = $request->device_id ?? 'No ID';
                $userdevice->timezone = $request->timezone ?? 'No Time';
                $userdevice->token = $request->fcm_token ?? 'No tocken';
                $userdevice->save();

                $user->platform  = 'normal';
                $user->accounts = [];

                return response()->json([
                    'status' => true,
                    'action' => "Login successfully",
                    'data' => $user,
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'action' => 'Password is invalid, please enter a valid Password',
                ]);
            }
        }
        return response()->json([
            'status' => false,
            'action' => "This Email Address or Username is not registered ",

        ]);
    }

    public function listUser($id)
    {
        $users = User::select('id', 'name', 'image', 'username', 'verify')->where('id', '!=', $id)->latest()->paginate(12);
        return response()->json([
            'status' => true,
            'action' => 'Users',
            'data' => $users
        ]);
    }
    public function recover(RecoverVerifyRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $otp = random_int(100000, 999999);
            $user = User::where('email', $request->email)->update([
                'otp' => $otp,
                'otp_time' => strtotime(date('Y-m-d H:i:s'))
            ]);

            $mail_details = [
                'body' => $otp,
            ];

            Mail::to($request->email)->send(new OtpSend($mail_details));

            return response()->json([
                'status' => true,
                'action' => 'OTP send successfully',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'action' => 'This Email Address is not registered'
            ]);
        }
    }


    public function recoverVerify(RecoverOtpVerify $request)
    {
        $user = User::where('email', $request->email)->first();

        if ($user) {

            if ($user->otp == $request->otp) {
                User::where('email', '=', $request->email)->update([
                    'otp' => '',
                    'otp_time' => ''
                ]);

                return response()->json([
                    'status' => true,
                    'action' => 'OTP verify successfully'
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'action' => 'OTP is invalid, Please enter a valid OTP'
                ]);
            }
        } else {
            return response()->json([
                'status' => false,
                'action' => 'This Email Address is not registered'
            ]);
        }
    }


    public function newPassword(NewPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'action' => "New password is same as Old password",
                ]);
            } else {
                $user->update([
                    'password' => Hash::make($request->password)
                ]);
                return response()->json([
                    'status' => true,
                    'action' => "New password set",
                ]);
            }
            // $user->update([
            //     'password' => Hash::make($request->password)
            // ]);
            return response()->json([
                'status' => true,
                'action' => "New Password set"
            ]);
        } else {
            return response()->json([
                'status' => false,
                'action' => 'This Email Address is not registered'
            ]);
        }
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = User::find($request->user_id);
        if ($user) {
            if (Hash::check($request->old_password, $user->password)) {
                if (Hash::check($request->new_password, $user->password)) {

                    return response()->json([
                        'status' => false,
                        'action' => "New password is same as old password",
                    ]);
                } else {
                    $user->update([
                        'password' => Hash::make($request->new_password)
                    ]);
                    return response()->json([
                        'status' => true,
                        'action' => "Password  change",
                    ]);
                }
            }
            return response()->json([
                'status' => false,
                'action' => "Old password is wrong",
            ]);
        } else {
            return response()->json([
                'status' => false,
                'action' => 'User not found'
            ]);
        }
    }
    public function logout(LogoutRequest $request)
    {
        UserDevice::where('user_id', $request->user_id)->where('device_id', $request->device_id)->delete();

        return response()->json([
            'status' => true,
            'action' => 'User logged out'
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'password' => 'required',
        ]);
        $errorMessage = implode(', ', $validator->errors()->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'action' => $errorMessage
            ]);
        }

        $user = User::find($request->user_id);
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $user->delete();
                return response()->json([
                    'status' => true,
                    'action' => "Account deleted",
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'action' => 'Please enter correct password',
                ]);
            }
        } else {
            return response()->json([
                'status' => false,
                'action' => "User not found"
            ]);
        }
    }


    public function socialLogin(SocialLoginRequest $request)
    {
        $normal = User::where('email', $request->platform_email)->first();
        if ($normal) {
            $normal = true;
        } else {
            $normal = false;
        }

        $user = Social::where('platform', $request->platform)->where('platform_id', $request->platform_id)->where('platform_email', $request->platform_email)->first();
        if ($user) {
            $data = User::find($user->user_id);
            if ($data) {
                $userdevice = new UserDevice();
                $userdevice->user_id = $user->id;
                $userdevice->device_name = $request->device_name ?? 'No name';
                $userdevice->device_id = $request->device_id ?? 'No ID';
                $userdevice->timezone = $request->timezone ?? 'No Time';
                $userdevice->token = $request->fcm_token ?? 'No tocken';
                $userdevice->save();

                $platform = Social::where('user_id', $user->user_id)->get();
                $data->platform  =  $request->platform;
                $data->accounts = $platform;

                return response()->json([
                    'status' => true,
                    'action' => "Login Successfuly",
                    'data' => $data,
                    'normal' => $normal

                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'action' => "Email Address is not connected with Social Account",
                    'normal' => $normal
                ]);
            }
        } else {
            return response()->json([
                'status' => false,
                'action' => "Email Address is not connected with Social Account",
                'normal' => $normal
            ]);
        }
    }

    public function socialConnect(SocialConnectRequest $request)
    {
        $find = Social::where('platform', $request->platform)->where('user_id', $request->user_id)->first();
        if ($find) {
            return response()->json([
                'status' => false,
                'action' =>  'Account already exist on this platform',
            ]);
        }
        $social = new Social();
        $social->user_id = $request->user_id;

        $social->platform = $request->platform;
        $social->platform_id = $request->platform_id;
        $social->platform_email = $request->platform_email;
        $social->save();
        $account = Social::where('user_id', $request->user_id)->get();


        return response()->json([
            'status' => true,
            'action' =>  'Connected Successfully',
            'data' => $account
        ]);
    }

    public function removeSocial(RemoveSocialRequest $request)
    {


        $remove = Social::where('user_id', $request->user_id)->where('platform', $request->platform)->where('platform_email', $request->platform_email)->first();
        if ($remove) {
            $remove->delete();

            $accounts = Social::where('user_id', $request->user_id)->get();
            return response()->json([
                'status' => true,
                'action' =>  'Social account removed',
                'data' => $accounts
            ]);
        } else {
            return response()->json([
                'status' => false,
                'action' =>  'Please enter correct Email and Platform',
            ]);
        }
    }

    public function getSocial($id)
    {
        $user = User::find($id);
        if ($user) {
            $social = Social::where('user_id', $id)->get();
            return response()->json([
                'status' => true,
                'action' =>  'Social Accounts',
                'data' => $social
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  'User not found',
        ]);
    }

    public function editProfile(EditProfileRequest $request)
    {

        $user = User::find($request->user_id);
        if ($user) {
            if ($request->has('full_name')) {
                $user->name = $request->full_name;
            }

            if ($request->has('dob')) {
                if ($request->dob == null) {
                    $user->dob = '';
                } else {
                    $user->dob = $request->dob;
                }
            }


            if ($request->has('gender')) {
                if ($request->gender == null) {
                    $user->gender = '';
                } else {
                    $user->gender = $request->gender;
                }
            }

            if ($request->has('bio')) {
                if ($request->bio == null) {
                    $user->bio = '';
                } else {
                    $user->bio = $request->bio;
                }
            }



            if ($request->has('location')) {
                if ($request->location == null) {
                    $user->location = '';
                    $user->lat = '';
                    $user->lng = '';
                } else {
                    $user->location = $request->location;
                    $user->lat = $request->lat;
                    $user->lng = $request->lng;
                }
            }


            if ($request->has('email')) {
                if (User::where('email', $request->email)->where('id', '!=', $request->user_id)->exists()) {
                    return response()->json([
                        'status' => false,
                        'action' => 'Email Address already taken'
                    ]);
                } else {
                    $user->email = $request->email;
                }
            }

            if ($request->has('username')) {
                if (User::where('username', $request->username)->where('id', '!=', $request->user_id)->exists()) {
                    return response()->json([
                        'status' => false,
                        'action' => 'Username already taken'
                    ]);
                } else {
                    $user->username = $request->username;
                }
            }

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $mime = explode('/', $file->getClientMimeType());
                $filename = time() . '-' . uniqid() . '.' . $extension;
                if ($file->move('uploads/user/' . $request->user_id . '/profile/', $filename))
                    $image = '/uploads/user/' . $request->user_id . '/profile/' . $filename;
                $user->image = $image;
            }

            if ($request->hasFile('cover')) {
                $file = $request->file('cover');
                $extension = $file->getClientOriginalExtension();
                $mime = explode('/', $file->getClientMimeType());
                $filename = time() . '-' . uniqid() . '.' . $extension;
                if ($file->move('uploads/user/' . $request->user_id . '/profile/', $filename))
                    $cover = '/uploads/user/' . $request->user_id . '/profile/' . $filename;
                $user->cover = $cover;
            }


            $user->save();
            $user->platform = 'normal';
            return response()->json([
                'status' => true,
                'action' => "Profile edit",
                'data' => $user
            ]);
        } else {
            return response()->json([
                'status' => false,
                'action' => "User not found"
            ]);
        }
    }



    public function editImage(Request $request)
    {

        $user = User::find($request->user_id);
        if ($user) {
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $mime = explode('/', $file->getClientMimeType());
                $filename = time() . '-' . uniqid() . '.' . $extension;
                if ($file->move('uploads/user/' . $request->user_id . '/profile/', $filename))
                    $image = '/uploads/user/' . $request->user_id . '/profile/' . $filename;
                $user->image = $image;
            }
            $user->save();


            return response()->json([
                'status' => true,
                'action' => "Profile edit",
                'data' => $user
            ]);
        }

        return response()->json([
            'status' => false,
            'action' => "User not found"
        ]);
    }


    public function removeImage($user_id,$type)
    {
        $user = User::find($user_id);
        if ($user) {
            if($type == 'image'){
                $user->image = '';
            }
            else{
                $user->cover = '';
            }
            $user->save();
            return response()->json([
                'status' => true,
                'action' => "Image remove",
                'data' => $user
            ]);
        } else {
            return response()->json([
                'status' => false,
                'action' => "User not found"
            ]);
        }
    }

    public function getVerify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required',
            'user_id' => 'required|exists:users,id',
        ]);

        $errorMessage = implode(', ', $validator->errors()->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'action' => $errorMessage
            ]);
        }
        $cehck = ImageVerify::where('user_id', $request->user_id)->first();
        if ($cehck) {
            return response()->json([
                'status' => true,
                'action' => "Request Already submited"
            ]);
        } else {
            $user = User::find($request->user_id);
            if ($user) {
                $userImage =  new ImageVerify();
                $file = $request->file('image');
                $extension = $file->getClientOriginalExtension();
                $mime = explode('/', $file->getClientMimeType());
                $filename = time() . '-' . uniqid() . '.' . $extension;
                if ($file->move('uploads/user/' . $request->user_id . '/verify/', $filename))
                    $image = '/uploads/user/' . $request->user_id . '/verify/' . $filename;

                $userImage->user_id = $request->user_id;
                $userImage->image = $image;
                $user->verify = 2;
                $userImage->save();
                $user->save();

                return response()->json([
                    'status' => true,
                    'action' => "Request submited"
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'action' => "User not found"
                ]);
            }
        }
    }

    public function blockList($id)
    {
        $block_ids = BlockList::where('user_id', $id)->pluck('block_id');
        $blockUsers = User::select('id', 'name', 'image', 'username', 'verify')->whereIn('id', $block_ids)->paginate(12);
        foreach ($blockUsers as $block) {
            $block->block = true;
        }

        return response()->json([
            'status' => true,
            'action' =>  'Block list',
            'data' => $blockUsers
        ]);
    }

    public function makePrivate($id, $status)
    {
        $user = User::find($id);
        if ($user) {
            $user->is_public = $status;
            $user->save();

            $user = User::find($id);
            return response()->json([
                'status' => true,
                'action' =>  'User',
                'data' => $user
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  'No user Found',
        ]);
    }

    public function hideCounter($id, $status)
    {
        $user = User::find($id);
        if ($user) {
            $user->counter = $status;
            $user->save();
            $user = User::find($id);

            return response()->json([
                'status' => true,
                'action' =>  'User',
                'data' => $user
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  'No user Found',
        ]);
    }
}
