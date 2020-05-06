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

use App\Service\DependencyInjecion\Scanner\OHLCV\SQLExpressionLanguageProvider;


class ScannerExpression extends \Symfony\Component\ExpressionLanguage\ExpressionLanguage
{
    protected $em;

    protected $instrument;

    public function __construct(
      \Symfony\Bridge\Doctrine\RegistryInterface $registry
    )
    {
        $this->em = $registry->getManager();

        $this->registerProvider(new SQLExpressionLanguageProvider($this->em));

        parent::__construct();
    }

    public function setInstrument(\App\Entity\Instrument $instrument)
    {
        $this->instrument = $instrument;
    }
}