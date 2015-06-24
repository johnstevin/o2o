<?php
namespace Apimerchant\Controller;
class NormsController extends ApiController
{
    public function lists($categoryIds = null, $brandIds, $pageSize = null)
    {
        $this->apiSuccess();
    }
}