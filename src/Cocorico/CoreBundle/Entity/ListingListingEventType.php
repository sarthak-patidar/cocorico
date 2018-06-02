<?php

namespace Cocorico\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ListingListingEventType
 *
 * @ORM\Table(name="listing_listing_event_type")
 * @ORM\Entity
 */
class ListingListingEventType
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Listing
     *
     * @ORM\ManyToOne(targetEntity="Listing", cascade={"persist"})
     * @ORM\JoinColumn(name="listing_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $listingId;

    /**
     * @var ListingEventType
     *
     * @ORM\ManyToOne(targetEntity="ListingEventType", cascade={"persist"})
     * @ORM\JoinColumn(name="event_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     *
     */
    private $eventId;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set listingId
     *
     * @param Listing $listing
     *
     * @return ListingListingEventType
     */
    public function setListingId(Listing $listing)
    {
        $this->listingId = $listing;

        return $this;
    }

    /**
     * Get listingId
     *
     * @return Listing
     */
    public function getListingId()
    {
        return $this->listingId;
    }

    /**
     * Set eventId
     *
     * @param ListingEventType $event
     *
     * @return ListingListingEventType
     */
    public function setEventId(ListingEventType $event)
    {
        $this->eventId = $event;

        return $this;
    }

    /**
     * Get eventId
     *
     * @return ListingEventType
     */
    public function getEventId()
    {
        return $this->eventId;
    }
}
