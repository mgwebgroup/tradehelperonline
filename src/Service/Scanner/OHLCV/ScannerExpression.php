<?php
/**
 * This file is part of the Trade Helper Online package.
 *
 * (c) 2019-2020  Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Scanner\OHLCV;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use App\Service\Exchange\Catalog;

/**
 * Class ScannerExpression
 * Registers simple functions used in scanners, like Close, High, etc.
 * @package App\Service\Scanner\OHLCV
 */
class ScannerExpression extends ExpressionLanguage
{
    protected $em;

    protected $instrument;

    protected $catalog;

    public function __construct(
      RegistryInterface $registry,
      Catalog $catalog
    )
    {
        $this->em = $registry->getManager();
        $this->catalog = $catalog;
        $this->registerProvider(new ScannerSimpleFunctionsProvider($this->em, $catalog));

        parent::__construct();
    }

    public function setInstrument(\App\Entity\Instrument $instrument)
    {
        $this->instrument = $instrument;
    }
}