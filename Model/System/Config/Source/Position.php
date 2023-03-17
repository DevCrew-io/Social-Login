<?php
/**
 * @author Devcrew Team
 * @copyright Copyright (c) 2023 Devcrew {https://devcrew.io}
 * Date: 26/01/2023
 * Time: 5:50 PM
 */
declare(strict_types=1);

namespace Devcrew\SocialLogin\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Source class Position
 *
 * Class Position
 */
class Position implements ArrayInterface
{
    /**#@+
     * Constants for array value
     */
    public const PAGE_LOGIN  = 1;
    public const PAGE_CREATE = 2;
    /**#@-*/

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => __('Select Position')],
            ['value' => self::PAGE_LOGIN, 'label' => __('Customer Login Page')],
            ['value' => self::PAGE_CREATE, 'label' => __('Customer Create Page')]
        ];
    }
}
