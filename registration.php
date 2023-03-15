<?php
/**
 * @author Devcrew Team
 * @copyright Copyright (c) 2023 Devcrew {https://devcrew.io}
 * Date: 25/01/2023
 * Time: 2:10 PM
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Devcrew_SocialLogin',
    __DIR__
);
