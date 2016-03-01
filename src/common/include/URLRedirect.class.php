<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * URLRedirect
 *
 * This class is responsible for
 * the redirection.
 */
class URLRedirect {

    /**
     * Build the redirection of user to the login page.
     */
    public function buildReturnToLogin($server){
        $returnTo = urlencode((($server['REQUEST_URI'] === "/") ? "/my/" : $server['REQUEST_URI']));
        $url = parse_url($server['REQUEST_URI']);
        if (isset($url['query'])) {
            $query = $url['query'];
            if (strstr($query, 'pv=2')) {
                $returnTo .= "&pv=2";
            }
        }
        if (strpos($url['path'], '/projects') === 0) {
            $GLOBALS['Response']->send401UnauthorizedHeader();
        }

        $url = '/account/login.php?return_to=' . $returnTo;
        return $url;
    }

    public function redirectToLogin(){
        $url = $this->buildReturnToLogin($_SERVER);
        $GLOBALS['HTML']->redirect($url);
    }

    public function makeReturnToUrl(HTTPRequest $request, $url) {
        $urlToken = parse_url($url);

        $finaleUrl = '';

        $server_url = '';
        if(array_key_exists('host', $urlToken) && $urlToken['host']) {
            $server_url = $urlToken['scheme'].'://'.$urlToken['host'];
            if(array_key_exists('port', $urlToken) && $urlToken['port']) {
                $server_url .= ':'.$urlToken['port'];
            }
        } else {
            if ($request->isSSL() && $this->shouldRedirectToHTTP($request)) {
                $server_url = 'http://'.$GLOBALS['sys_default_domain'];
            }
        }

        $finaleUrl = $server_url;

        if(array_key_exists('path', $urlToken) && $urlToken['path']) {
            $finaleUrl .= $urlToken['path'];
        }

        if($request->existAndNonEmpty('return_to')) {
            $return_to_parameter = 'return_to=';
            /*
             * We do not want redirect to an external website
             * @see https://cwe.mitre.org/data/definitions/601.html
             */
            $url_verifier = new URLVerification();
            if ($url_verifier->isInternal($request->get('return_to'))) {
                $return_to_parameter .= urlencode($request->get('return_to'));
            } else {
                $return_to_parameter .= '/';
            }

            if(array_key_exists('query', $urlToken) && $urlToken['query']) {
                $finaleUrl .= '?'.$urlToken['query'].'&amp;'.$return_to_parameter;
            }
            else {
                $finaleUrl .= '?'.$return_to_parameter;
            }
            if (strstr($request->get('return_to'),'pv=2')) {
                $finaleUrl .= '&pv=2';
            }
        }
        else {
            if(array_key_exists('query', $urlToken) && $urlToken['query']) {
                $finaleUrl .= '?'.$urlToken['query'];
            }
        }

        if(array_key_exists('fragment', $urlToken) && $urlToken['fragment']) {
            $finaleUrl .= '#'.$urlToken['fragment'];
        }

        return $finaleUrl;
    }

    private function shouldRedirectToHTTP(HTTPRequest $request) {
        return $this->SSLIsNotMandatory() && $this->userAskedForHTTP($request);
    }

    private function SSLIsNotMandatory() {
        return ! $GLOBALS['sys_force_ssl'];
    }

    private function userAskedForHTTP(HTTPRequest $request) {
        return ! $request->get('stay_in_ssl');
    }
}
