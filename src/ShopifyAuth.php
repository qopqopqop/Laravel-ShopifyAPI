<?php

namespace BNMetrics\Shopify;

use BNMetrics\Shopify\Traits\ResponseOptions;
use Illuminate\Http\Request;
use Laravel\Socialite\Two\User;
use Laravel\Socialite\Two\AbstractProvider;

class ShopifyAuth extends AbstractProvider
{
    use ResponseOptions;

    protected $shopURL;

    protected $adminPath = "/admin/";

    protected $requestPath;

    /**
     * Set the myshopify domain URL for the API request.
     * eg. example.myshopify.com
     *
     * @param Request $shopURL
     * @return $this
     */
    public function setShopURL($shopURL)
    {
        $this->shopURL = $shopURL;

        return $this;
    }

    /**
     * Get the API request path
     *
     * @return string
     */
    public function requestPath()
    {
        if($this->shopURL != null)
            $this->requestPath = 'https://' . $this->shopURL . $this->adminPath;

        return $this->requestPath;
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     * @return string
     */
    protected function getAuthUrl($state)
    {
        $url =  $this->requestPath()."oauth/authorize";

        return $this->buildAuthUrlFromBase( $url, $state );
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        // 'https://example.myshopify.com/admin/oauth/access_token'
        return 'https://' . $this->shopURL . $this->adminPath . "oauth/access_token";
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param  string $token
     * @return array
     */
    protected function getUserByToken($token)
    {
        $userUrl = 'https://' . $this->shopURL . $this->adminPath . "shop.json";


        $response = $this->getHttpClient()->get( $userUrl,
                [
                    'headers' => $this->getResponseHeaders($token)
                ]);

        $user = json_decode($response->getBody(), true);

        return $user['shop'];
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param  array $user
     * @return \Laravel\Socialite\Two\User
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'],
            'nickname' => $user['name'],
            'name' => $user['myshopify_domain'],
            'email' => $user['email'],
            'avatar' => null
        ]);
    }


    /**
     * this method is for when you need to make an embedded shopify app
     *
     * @return string
     */
    public function fetchAuthUrl()
    {
        $state = $this->getState();

        $authUrl = $this->getAuthUrl($state);

        return $authUrl;
    }

}