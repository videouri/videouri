<?php

namespace App\Traits;

use App\Entities\User;
use App\Exceptions\SocialAuthException;
use Cocur\Slugify\Slugify;
use Auth;
use Log;
use Redirect;
use Socialite;
use Validator;

/**
 * Class SocialAuth
 * @package App\Traits
 */
trait SocialAuth
{
    /**
     * @param string $provider
     * @return Redirect
     */
    public function redirectToProvider($provider)
    {
        if (in_array($provider, ['facebook', 'twitter'])) {
            return Socialite::driver($provider)->redirect();
        }

        return redirect('login');
    }

    /**
     * @param string $provider
     * @return Redirect
     */
    public function handleProviderCallback($provider)
    {
        $userData = Socialite::driver($provider)->user();

        ///////////////////////
        // Collect user data //
        ///////////////////////
        $userId = $userData->getId();
        $email = $userData->getEmail();
        $fullName = $userData->getName();
        $userName = $userData->getNickname();
        $avatar = $userData->getAvatar();

        if (empty($email)) {
            $email = mt_rand(100, 9999) . '-' . time() . '@missingemail.com';
        }

        if (empty($userName)) {
            $slugify = new Slugify();
            $userName = $slugify->slugify($fullName);
        }

        $user = User::where('provider_id', '=', $userId)->first();
        if ($user) {
            Auth::login($user, true);
            return redirect('/');
        }

        $emailExists = User::where('email', '=', $email)->first();
        if (!$user && $emailExists) {
            return redirect('/login')->withErrors([
                'Email ' . $email . ' is already in use.',
            ]);
        }

        /////////////////////////
        // Validate and create //
        /////////////////////////

        $userData = [
            'username' => $userName,
            'email' => $email,
            'avatar' => $avatar,
            'provider' => $provider,
            'provider_id' => $userId,
        ];

        $this->validateParameters($userData);

        $this->createUser($userData);
    }

    /**
     * @param $userData
     * @return mixed
     */
    private function validateParameters($userData)
    {
        $validator = Validator::make($userData, [
            'username' => 'required|max:255|unique:users',
            'email' => 'required|email|max:255|unique:users',
        ]);

        if ($validator->fails()) {
            return redirect('/login')->withErrors($validator)
                ->withInput();
        }
    }

    /**
     * @param $userData
     * @throws SocialAuthException
     *
     * @return Redirect
     */
    private function createUser($userData)
    {
        $user = new User($userData);

        if ($user->save()) {
            Auth::login($user, true);
            return redirect('/');
        } else {
            Log::error('Social auth: Creating user failure. User data: ' . serialize($userData));
            throw new SocialAuthException('Internal error, couldn\'t create the user');
        }
    }
}
