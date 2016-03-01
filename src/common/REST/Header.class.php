<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\REST;

use ForgeConfig;

class Header {
    const GET     = 'GET';
    const OPTIONS = 'OPTIONS';
    const PUT     = 'PUT';
    const POST    = 'POST';
    const DELETE  = 'DELETE';
    const PATCH   = 'PATCH';

    const CORS_ALLOW_METHODS = 'Access-Control-Allow-Methods';
    const ALLOW              = 'Allow';
    const LAST_MODIFIED      = 'Last-Modified';
    const ETAG               = 'Etag';
    const LOCATION           = 'Location';

    const X_PAGINATION_LIMIT     = 'X-PAGINATION-LIMIT';
    const X_PAGINATION_OFFSET    = 'X-PAGINATION-OFFSET';
    const X_PAGINATION_SIZE      = 'X-PAGINATION-SIZE';
    const X_PAGINATION_LIMIT_MAX = 'X-PAGINATION-LIMIT-MAX';

    const X_UPLOAD_MAX_FILE_CHUNKSIZE = 'X-UPLOAD-MAX-FILE-CHUNKSIZE';

    public static function lastModified($timestamp) {
        self::sendHeader(self::LAST_MODIFIED, date('c', $timestamp));
    }

    public static function ETag($hash) {
        self::sendHeader(self::ETAG, $hash);
    }

    public static function Location($uri) {
        $route = 'https://' . ForgeConfig::get('sys_default_domain') . $uri;

        self::sendHeader(self::LOCATION, $route);
    }

    public static function allowOptions() {
        self::sendAllowHeaders(array(self::OPTIONS));
    }

    public static function allowOptionsGet() {
        self::sendAllowHeaders(array(self::OPTIONS, self::GET));
    }

    public static function allowOptionsPostDelete() {
        self::sendAllowHeaders(array(self::OPTIONS, self::POST, self::DELETE));
    }

    public static function allowOptionsDelete() {
        self::sendAllowHeaders(array(self::OPTIONS, self::DELETE));
    }

    public static function allowOptionsGetPut() {
        self::sendAllowHeaders(array(self::OPTIONS, self::GET, self::PUT));
    }

    public static function allowOptionsGetPutPost() {
        self::sendAllowHeaders(array(self::OPTIONS, self::GET, self::PUT, self::POST));
    }

    public static function allowOptionsGetPutPostPatch() {
        self::sendAllowHeaders(array(self::OPTIONS, self::GET, self::PUT, self::POST, self::PATCH));
    }

    public static function allowOptionsGetPutPatch() {
        self::sendAllowHeaders(array(self::OPTIONS, self::GET, self::PUT, self::PATCH));
    }

    public static function allowOptionsGetPutDelete() {
        self::sendAllowHeaders(array(self::OPTIONS, self::GET, self::PUT, self::DELETE));
    }

    public static function allowOptionsPut() {
        self::sendAllowHeaders(array(self::OPTIONS, self::PUT));
    }

    public static function allowOptionsPost() {
        self::sendAllowHeaders(array(self::OPTIONS, self::POST));
    }

    public static function allowOptionsPostPut() {
        self::sendAllowHeaders(array(self::OPTIONS, self::POST, self::PUT));
    }

    public static function allowOptionsGetPost() {
        self::sendAllowHeaders(array(self::OPTIONS, self::GET, self::POST));
    }

    public static function allowOptionsGetPatch() {
        self::sendAllowHeaders(array(self::OPTIONS, self::GET, self::PATCH));
    }

    public static function allowOptionsGetPatchDelete() {
        self::sendAllowHeaders(array(self::OPTIONS, self::GET, self::PATCH, self::DELETE));
    }

    public static function allowOptionsPatchDelete() {
        self::sendAllowHeaders(array(self::OPTIONS, self::PATCH, self::DELETE));
    }

    public static function allowOptionsPatch() {
        self::sendAllowHeaders(array(self::OPTIONS, self::PATCH));
    }

    public static function allowOptionsPostPatch() {
        self::sendAllowHeaders(array(self::OPTIONS, self::POST, self::PATCH));
    }

    private static function sendAllowHeaders($methods) {
        $methods = implode(', ', $methods);
        self::sendHeader(self::ALLOW, $methods);
        self::sendHeader(self::CORS_ALLOW_METHODS, $methods);
    }

    public static function sendPaginationHeaders($limit, $offset, $size, $max_limit) {
        self::sendHeader(self::X_PAGINATION_LIMIT, $limit);
        self::sendHeader(self::X_PAGINATION_OFFSET, $offset);
        self::sendHeader(self::X_PAGINATION_SIZE, $size);
        self::sendHeader(self::X_PAGINATION_LIMIT_MAX, $max_limit);
    }

    public static function sendOptionsPaginationHeaders($limit, $offset, $max_limit) {
        self::sendHeader(self::X_PAGINATION_LIMIT, $limit);
        self::sendHeader(self::X_PAGINATION_OFFSET, $offset);
        self::sendHeader(self::X_PAGINATION_LIMIT_MAX, $max_limit);
    }

    public static function sendMaxFileChunkSizeHeaders($size) {
        self::sendHeader(self::X_UPLOAD_MAX_FILE_CHUNKSIZE, $size);
    }

    private static function sendHeader($name, $value) {
        header($name .': '. $value);
    }
}
