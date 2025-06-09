<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponseTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseLaravelController;

/**
 * @version V1.0 - LomisX3 標準基礎控制器
 * @description 所有 API 控制器的統一基類，提供標準化的回應格式和共用功能。
 */
class BaseController extends BaseLaravelController
{
    use AuthorizesRequests, ValidatesRequests, ApiResponseTrait;
} 