<?php

namespace TecCom\Order\TXML5\Order\OrderAType;

/**
 * Class representing HeadAType
 */
class HeadAType
{
    /**
     * The type of the order.
     *
     * @var string $orderType
     */
    private $orderType = null;

    /**
     * The identifier of the order (aka the buyer order number).
     *
     * Later business documents of the seller (or, sometimes, the buyer) will use this identifier to reference the order.
     *
     * The identifier **must** be unique over all orders the buyer sends (that is, no two orders may share the same identifier).
     *
     * @var string $documentNumber
     */
    private $documentNumber = null;

    /**
     * The specific point in time when the order was created.
     *
     * It **must** include the time zone (which is the number of hours and minutes the given date is ahead or behind UTC time).
     *
     * @var \DateTime $issueDate
     */
    private $issueDate = null;

    /**
     * A reference to another document.
     *
     * 1. The buyer **must not** add references with the qualifiers `BuyerOrderNumber`or `SellerOrderNumber`. 
     *
     * 2. The buyer **must** add references with the qualifiers `BuyerOrderNumber`or `SellerOrderNumber` when the order refers to a previously placed order as follows:
     *
     *  1. A change order after a standard or rush order
     *  2. A call-off order after a blanket order
     *  3. A consumption order after a consignment order
     *
     * @var \TecCom\Order\TXML5\ReferenceHeaderType[] $reference
     */
    private $reference = [
        
    ];

    /**
     * Information about the seller.
     *
     * The `Number` for the seller party is usually assigned by the buyer. It is the number under which the seller is known in the buyer's ERP system.
     *
     * @var \TecCom\Order\TXML5\PartyType1Type $sellerParty
     */
    private $sellerParty = null;

    /**
     * Information about the buyer.
     *
     * The `Number` for the buyer party is usually assigned by the seller. It is the number under which the buyer is known in the seller's ERP system.
     *
     * @var \TecCom\Order\TXML5\PartyType1Type $buyerParty
     */
    private $buyerParty = null;

    /**
     * Information about the recipient of the order.
     *
     * The delivery party **must not** be used to provide a shipping address for dropshipping. For that an `AdditionalParty` **must** be used.
     *
     * @var \TecCom\Order\TXML5\PartyType2Type $deliveryParty
     */
    private $deliveryParty = null;

    /**
     * Information about another party involved in the order.
     *
     * @var \TecCom\Order\TXML5\AdditionalPartyType[] $additionalParty
     */
    private $additionalParty = [
        
    ];

    /**
     * Information about the currency of the order.
     *
     * A buyer **must** only use the `Request` element, but omit `Response` and `ExchangeRate`.
     *
     * @var \TecCom\Order\TXML5\CurrencyType $currency
     */
    private $currency = null;

    /**
     * The processing instructions the seller **must** follow when processing the order.
     *
     * @var \TecCom\Order\TXML5\OrderProcessingInstructionsType $processingInstructions
     */
    private $processingInstructions = null;

    /**
     * An additional parameter that conveys specific information that applies to the whole document.
     *
     * The buyer **must** only use these qualifiers in an order:
     *
     * 1. `PromotionCode`
     * 2. `StorageLocation`
     *
     * @var \TecCom\Order\TXML5\AdditionalParametersType[] $additionalParameters
     */
    private $additionalParameters = [
        
    ];

    /**
     * Information about the terms of payment.
     *
     * 1. If present, the seller **must** evaluate the mode sent by the buyer and reject the order if they sent an unsupported payment mode.
     *
     * 2. For the payment mode `PayBeforeOrder`:
     *
     *  1. The buyer **must** send the payment reference in the order that was previously returned by the seller in the availability response.
     *
     *  2. The seller **must** reject the order if the payment for this payment reference has not been received.
     *
     * @var \TecCom\Order\TXML5\PaymentType $payment
     */
    private $payment = null;

    /**
     * The Incoterms rule.
     *
     * @var \TecCom\Order\TXML5\IncotermsType $incoterms
     */
    private $incoterms = null;

    /**
     * Arbitrary information that relates to the whole document.
     *
     * Free text **must** be agreed between buyer and seller, otherwise the seller will ignore it.
     *
     * @var \TecCom\Order\TXML5\FreeTextValueType[] $freeText
     */
    private $freeText = [
        
    ];

    /**
     * Gets as orderType
     *
     * The type of the order.
     *
     * @return string
     */
    public function getOrderType()
    {
        return $this->orderType;
    }

    /**
     * Sets a new orderType
     *
     * The type of the order.
     *
     * @param string $orderType
     * @return self
     */
    public function setOrderType($orderType)
    {
        $this->orderType = $orderType;
        return $this;
    }

    /**
     * Gets as documentNumber
     *
     * The identifier of the order (aka the buyer order number).
     *
     * Later business documents of the seller (or, sometimes, the buyer) will use this identifier to reference the order.
     *
     * The identifier **must** be unique over all orders the buyer sends (that is, no two orders may share the same identifier).
     *
     * @return string
     */
    public function getDocumentNumber()
    {
        return $this->documentNumber;
    }

    /**
     * Sets a new documentNumber
     *
     * The identifier of the order (aka the buyer order number).
     *
     * Later business documents of the seller (or, sometimes, the buyer) will use this identifier to reference the order.
     *
     * The identifier **must** be unique over all orders the buyer sends (that is, no two orders may share the same identifier).
     *
     * @param string $documentNumber
     * @return self
     */
    public function setDocumentNumber($documentNumber)
    {
        $this->documentNumber = $documentNumber;
        return $this;
    }

    /**
     * Gets as issueDate
     *
     * The specific point in time when the order was created.
     *
     * It **must** include the time zone (which is the number of hours and minutes the given date is ahead or behind UTC time).
     *
     * @return \DateTime
     */
    public function getIssueDate()
    {
        return $this->issueDate;
    }

    /**
     * Sets a new issueDate
     *
     * The specific point in time when the order was created.
     *
     * It **must** include the time zone (which is the number of hours and minutes the given date is ahead or behind UTC time).
     *
     * @param \DateTime $issueDate
     * @return self
     */
    public function setIssueDate(\DateTime $issueDate)
    {
        $this->issueDate = $issueDate;
        return $this;
    }

    /**
     * Adds as reference
     *
     * A reference to another document.
     *
     * 1. The buyer **must not** add references with the qualifiers `BuyerOrderNumber`or `SellerOrderNumber`. 
     *
     * 2. The buyer **must** add references with the qualifiers `BuyerOrderNumber`or `SellerOrderNumber` when the order refers to a previously placed order as follows:
     *
     *  1. A change order after a standard or rush order
     *  2. A call-off order after a blanket order
     *  3. A consumption order after a consignment order
     *
     * @return self
     * @param \TecCom\Order\TXML5\ReferenceHeaderType $reference
     */
    public function addToReference(\TecCom\Order\TXML5\ReferenceHeaderType $reference)
    {
        $this->reference[] = $reference;
        return $this;
    }

    /**
     * isset reference
     *
     * A reference to another document.
     *
     * 1. The buyer **must not** add references with the qualifiers `BuyerOrderNumber`or `SellerOrderNumber`. 
     *
     * 2. The buyer **must** add references with the qualifiers `BuyerOrderNumber`or `SellerOrderNumber` when the order refers to a previously placed order as follows:
     *
     *  1. A change order after a standard or rush order
     *  2. A call-off order after a blanket order
     *  3. A consumption order after a consignment order
     *
     * @param int|string $index
     * @return bool
     */
    public function issetReference($index)
    {
        return isset($this->reference[$index]);
    }

    /**
     * unset reference
     *
     * A reference to another document.
     *
     * 1. The buyer **must not** add references with the qualifiers `BuyerOrderNumber`or `SellerOrderNumber`. 
     *
     * 2. The buyer **must** add references with the qualifiers `BuyerOrderNumber`or `SellerOrderNumber` when the order refers to a previously placed order as follows:
     *
     *  1. A change order after a standard or rush order
     *  2. A call-off order after a blanket order
     *  3. A consumption order after a consignment order
     *
     * @param int|string $index
     * @return void
     */
    public function unsetReference($index)
    {
        unset($this->reference[$index]);
    }

    /**
     * Gets as reference
     *
     * A reference to another document.
     *
     * 1. The buyer **must not** add references with the qualifiers `BuyerOrderNumber`or `SellerOrderNumber`. 
     *
     * 2. The buyer **must** add references with the qualifiers `BuyerOrderNumber`or `SellerOrderNumber` when the order refers to a previously placed order as follows:
     *
     *  1. A change order after a standard or rush order
     *  2. A call-off order after a blanket order
     *  3. A consumption order after a consignment order
     *
     * @return \TecCom\Order\TXML5\ReferenceHeaderType[]
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Sets a new reference
     *
     * A reference to another document.
     *
     * 1. The buyer **must not** add references with the qualifiers `BuyerOrderNumber`or `SellerOrderNumber`. 
     *
     * 2. The buyer **must** add references with the qualifiers `BuyerOrderNumber`or `SellerOrderNumber` when the order refers to a previously placed order as follows:
     *
     *  1. A change order after a standard or rush order
     *  2. A call-off order after a blanket order
     *  3. A consumption order after a consignment order
     *
     * @param \TecCom\Order\TXML5\ReferenceHeaderType[] $reference
     * @return self
     */
    public function setReference(?array $reference = null)
    {
        $this->reference = $reference;
        return $this;
    }

    /**
     * Gets as sellerParty
     *
     * Information about the seller.
     *
     * The `Number` for the seller party is usually assigned by the buyer. It is the number under which the seller is known in the buyer's ERP system.
     *
     * @return \TecCom\Order\TXML5\PartyType1Type
     */
    public function getSellerParty()
    {
        return $this->sellerParty;
    }

    /**
     * Sets a new sellerParty
     *
     * Information about the seller.
     *
     * The `Number` for the seller party is usually assigned by the buyer. It is the number under which the seller is known in the buyer's ERP system.
     *
     * @param \TecCom\Order\TXML5\PartyType1Type $sellerParty
     * @return self
     */
    public function setSellerParty(\TecCom\Order\TXML5\PartyType1Type $sellerParty)
    {
        $this->sellerParty = $sellerParty;
        return $this;
    }

    /**
     * Gets as buyerParty
     *
     * Information about the buyer.
     *
     * The `Number` for the buyer party is usually assigned by the seller. It is the number under which the buyer is known in the seller's ERP system.
     *
     * @return \TecCom\Order\TXML5\PartyType1Type
     */
    public function getBuyerParty()
    {
        return $this->buyerParty;
    }

    /**
     * Sets a new buyerParty
     *
     * Information about the buyer.
     *
     * The `Number` for the buyer party is usually assigned by the seller. It is the number under which the buyer is known in the seller's ERP system.
     *
     * @param \TecCom\Order\TXML5\PartyType1Type $buyerParty
     * @return self
     */
    public function setBuyerParty(\TecCom\Order\TXML5\PartyType1Type $buyerParty)
    {
        $this->buyerParty = $buyerParty;
        return $this;
    }

    /**
     * Gets as deliveryParty
     *
     * Information about the recipient of the order.
     *
     * The delivery party **must not** be used to provide a shipping address for dropshipping. For that an `AdditionalParty` **must** be used.
     *
     * @return \TecCom\Order\TXML5\PartyType2Type
     */
    public function getDeliveryParty()
    {
        return $this->deliveryParty;
    }

    /**
     * Sets a new deliveryParty
     *
     * Information about the recipient of the order.
     *
     * The delivery party **must not** be used to provide a shipping address for dropshipping. For that an `AdditionalParty` **must** be used.
     *
     * @param \TecCom\Order\TXML5\PartyType2Type $deliveryParty
     * @return self
     */
    public function setDeliveryParty(?\TecCom\Order\TXML5\PartyType2Type $deliveryParty = null)
    {
        $this->deliveryParty = $deliveryParty;
        return $this;
    }

    /**
     * Adds as additionalParty
     *
     * Information about another party involved in the order.
     *
     * @return self
     * @param \TecCom\Order\TXML5\AdditionalPartyType $additionalParty
     */
    public function addToAdditionalParty(\TecCom\Order\TXML5\AdditionalPartyType $additionalParty)
    {
        $this->additionalParty[] = $additionalParty;
        return $this;
    }

    /**
     * isset additionalParty
     *
     * Information about another party involved in the order.
     *
     * @param int|string $index
     * @return bool
     */
    public function issetAdditionalParty($index)
    {
        return isset($this->additionalParty[$index]);
    }

    /**
     * unset additionalParty
     *
     * Information about another party involved in the order.
     *
     * @param int|string $index
     * @return void
     */
    public function unsetAdditionalParty($index)
    {
        unset($this->additionalParty[$index]);
    }

    /**
     * Gets as additionalParty
     *
     * Information about another party involved in the order.
     *
     * @return \TecCom\Order\TXML5\AdditionalPartyType[]
     */
    public function getAdditionalParty()
    {
        return $this->additionalParty;
    }

    /**
     * Sets a new additionalParty
     *
     * Information about another party involved in the order.
     *
     * @param \TecCom\Order\TXML5\AdditionalPartyType[] $additionalParty
     * @return self
     */
    public function setAdditionalParty(?array $additionalParty = null)
    {
        $this->additionalParty = $additionalParty;
        return $this;
    }

    /**
     * Gets as currency
     *
     * Information about the currency of the order.
     *
     * A buyer **must** only use the `Request` element, but omit `Response` and `ExchangeRate`.
     *
     * @return \TecCom\Order\TXML5\CurrencyType
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Sets a new currency
     *
     * Information about the currency of the order.
     *
     * A buyer **must** only use the `Request` element, but omit `Response` and `ExchangeRate`.
     *
     * @param \TecCom\Order\TXML5\CurrencyType $currency
     * @return self
     */
    public function setCurrency(?\TecCom\Order\TXML5\CurrencyType $currency = null)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * Gets as processingInstructions
     *
     * The processing instructions the seller **must** follow when processing the order.
     *
     * @return \TecCom\Order\TXML5\OrderProcessingInstructionsType
     */
    public function getProcessingInstructions()
    {
        return $this->processingInstructions;
    }

    /**
     * Sets a new processingInstructions
     *
     * The processing instructions the seller **must** follow when processing the order.
     *
     * @param \TecCom\Order\TXML5\OrderProcessingInstructionsType $processingInstructions
     * @return self
     */
    public function setProcessingInstructions(\TecCom\Order\TXML5\OrderProcessingInstructionsType $processingInstructions)
    {
        $this->processingInstructions = $processingInstructions;
        return $this;
    }

    /**
     * Adds as additionalParameters
     *
     * An additional parameter that conveys specific information that applies to the whole document.
     *
     * The buyer **must** only use these qualifiers in an order:
     *
     * 1. `PromotionCode`
     * 2. `StorageLocation`
     *
     * @return self
     * @param \TecCom\Order\TXML5\AdditionalParametersType $additionalParameters
     */
    public function addToAdditionalParameters(\TecCom\Order\TXML5\AdditionalParametersType $additionalParameters)
    {
        $this->additionalParameters[] = $additionalParameters;
        return $this;
    }

    /**
     * isset additionalParameters
     *
     * An additional parameter that conveys specific information that applies to the whole document.
     *
     * The buyer **must** only use these qualifiers in an order:
     *
     * 1. `PromotionCode`
     * 2. `StorageLocation`
     *
     * @param int|string $index
     * @return bool
     */
    public function issetAdditionalParameters($index)
    {
        return isset($this->additionalParameters[$index]);
    }

    /**
     * unset additionalParameters
     *
     * An additional parameter that conveys specific information that applies to the whole document.
     *
     * The buyer **must** only use these qualifiers in an order:
     *
     * 1. `PromotionCode`
     * 2. `StorageLocation`
     *
     * @param int|string $index
     * @return void
     */
    public function unsetAdditionalParameters($index)
    {
        unset($this->additionalParameters[$index]);
    }

    /**
     * Gets as additionalParameters
     *
     * An additional parameter that conveys specific information that applies to the whole document.
     *
     * The buyer **must** only use these qualifiers in an order:
     *
     * 1. `PromotionCode`
     * 2. `StorageLocation`
     *
     * @return \TecCom\Order\TXML5\AdditionalParametersType[]
     */
    public function getAdditionalParameters()
    {
        return $this->additionalParameters;
    }

    /**
     * Sets a new additionalParameters
     *
     * An additional parameter that conveys specific information that applies to the whole document.
     *
     * The buyer **must** only use these qualifiers in an order:
     *
     * 1. `PromotionCode`
     * 2. `StorageLocation`
     *
     * @param \TecCom\Order\TXML5\AdditionalParametersType[] $additionalParameters
     * @return self
     */
    public function setAdditionalParameters(?array $additionalParameters = null)
    {
        $this->additionalParameters = $additionalParameters;
        return $this;
    }

    /**
     * Gets as payment
     *
     * Information about the terms of payment.
     *
     * 1. If present, the seller **must** evaluate the mode sent by the buyer and reject the order if they sent an unsupported payment mode.
     *
     * 2. For the payment mode `PayBeforeOrder`:
     *
     *  1. The buyer **must** send the payment reference in the order that was previously returned by the seller in the availability response.
     *
     *  2. The seller **must** reject the order if the payment for this payment reference has not been received.
     *
     * @return \TecCom\Order\TXML5\PaymentType
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * Sets a new payment
     *
     * Information about the terms of payment.
     *
     * 1. If present, the seller **must** evaluate the mode sent by the buyer and reject the order if they sent an unsupported payment mode.
     *
     * 2. For the payment mode `PayBeforeOrder`:
     *
     *  1. The buyer **must** send the payment reference in the order that was previously returned by the seller in the availability response.
     *
     *  2. The seller **must** reject the order if the payment for this payment reference has not been received.
     *
     * @param \TecCom\Order\TXML5\PaymentType $payment
     * @return self
     */
    public function setPayment(?\TecCom\Order\TXML5\PaymentType $payment = null)
    {
        $this->payment = $payment;
        return $this;
    }

    /**
     * Gets as incoterms
     *
     * The Incoterms rule.
     *
     * @return \TecCom\Order\TXML5\IncotermsType
     */
    public function getIncoterms()
    {
        return $this->incoterms;
    }

    /**
     * Sets a new incoterms
     *
     * The Incoterms rule.
     *
     * @param \TecCom\Order\TXML5\IncotermsType $incoterms
     * @return self
     */
    public function setIncoterms(?\TecCom\Order\TXML5\IncotermsType $incoterms = null)
    {
        $this->incoterms = $incoterms;
        return $this;
    }

    /**
     * Adds as freeText
     *
     * Arbitrary information that relates to the whole document.
     *
     * Free text **must** be agreed between buyer and seller, otherwise the seller will ignore it.
     *
     * @return self
     * @param \TecCom\Order\TXML5\FreeTextValueType $freeText
     */
    public function addToFreeText(\TecCom\Order\TXML5\FreeTextValueType $freeText)
    {
        $this->freeText[] = $freeText;
        return $this;
    }

    /**
     * isset freeText
     *
     * Arbitrary information that relates to the whole document.
     *
     * Free text **must** be agreed between buyer and seller, otherwise the seller will ignore it.
     *
     * @param int|string $index
     * @return bool
     */
    public function issetFreeText($index)
    {
        return isset($this->freeText[$index]);
    }

    /**
     * unset freeText
     *
     * Arbitrary information that relates to the whole document.
     *
     * Free text **must** be agreed between buyer and seller, otherwise the seller will ignore it.
     *
     * @param int|string $index
     * @return void
     */
    public function unsetFreeText($index)
    {
        unset($this->freeText[$index]);
    }

    /**
     * Gets as freeText
     *
     * Arbitrary information that relates to the whole document.
     *
     * Free text **must** be agreed between buyer and seller, otherwise the seller will ignore it.
     *
     * @return \TecCom\Order\TXML5\FreeTextValueType[]
     */
    public function getFreeText()
    {
        return $this->freeText;
    }

    /**
     * Sets a new freeText
     *
     * Arbitrary information that relates to the whole document.
     *
     * Free text **must** be agreed between buyer and seller, otherwise the seller will ignore it.
     *
     * @param \TecCom\Order\TXML5\FreeTextValueType[] $freeText
     * @return self
     */
    public function setFreeText(?array $freeText = null)
    {
        $this->freeText = $freeText;
        return $this;
    }
}

