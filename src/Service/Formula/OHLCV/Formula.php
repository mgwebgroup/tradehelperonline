<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Formula\OHLCV;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use App\Service\Exchange\Catalog;

/**
 * Class Formula
 * Registers simple functions used in scanners, like Close, High, etc.
 * @package App\Service\Scanner\OHLCV
 */
class Formula extends ExpressionLanguage
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
      Catalog $catalog
    )
    {
        $this->em = $registry->getManager();
        $this->catalog = $catalog;
        $this->registerProvider(new SimpleFunctionsProvider($this->em, $catalog));

        parent::__construct();
    }
}