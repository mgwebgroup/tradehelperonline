<?php

/*
 * Copyright (c) Art Kurbakov <alex110504@gmail.com>
 *
 * For the full copyright and licence information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Service\ExpressionHandler\OHLCV;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use App\Service\Exchange\Catalog;

/**
 * Class Calculator
 * Registers simple functions used in scanners and watch lists, like Close, High, etc.
 * @package App\Service\Scanner\OHLCV
 */
class Calculator extends ExpressionLanguage
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $em;

    /**
     * @var Catalog
     */
    protected $catalog;

    public function __construct(
        RegistryInterface $registry,
        Catalog $catalog,
        $resultCacheLifetime = 0
    ) {
        $this->em = $registry->getManager();
        $this->catalog = $catalog;
        $this->registerProvider(new SimpleFunctionsProvider($this->em, $catalog, $resultCacheLifetime));

        parent::__construct();
    }
}
