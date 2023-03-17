<?php
/**
 * @author Devcrew Team
 * @copyright Copyright (c) 2023 Devcrew {https://devcrew.io}
 * Date: 27/02/2023
 * Time: 12:59 PM
 */

namespace Devcrew\SocialLogin\Model\ResourceModel\Social;

use Devcrew\SocialLogin\Model\ResourceModel\Social;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Social Login collection class
 *
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(\Devcrew\SocialLogin\Model\Social::class, Social::class);
    }
}
