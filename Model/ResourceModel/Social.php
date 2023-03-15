<?php
/**
 * @author Devcrew Team
 * @copyright Copyright (c) 2023 Devcrew {https://devcrew.io}
 * Date: 27/02/2023
 * Time: 12:51 PM
 */

namespace Devcrew\SocialLogin\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Social Login Resource Model
 *
 * Class Social
 */
class Social extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('devcrew_social_customer', 'social_login_id');
    }
}
