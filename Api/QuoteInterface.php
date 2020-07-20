<?php
namespace Lmerchant\Checkout\Api;

interface QuoteInterface
{
    /**
     * POST for Quote
     * @param \Lmerchant\Checkout\Api\Data\QuoteRequestInterface $request
     * @return \Lmerchant\Checkout\Api\Data\QuoteResponseInterface
     */
    
    public function update($request);
}
