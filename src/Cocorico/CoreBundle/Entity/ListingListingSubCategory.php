<?php

namespace Cocorico\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ListingListingSubCategory
 *
 * @ORM\Table(name="listing_listing_sub_category")
 * @ORM\Entity
 */
class ListingListingSubCategory
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
     * @var ListingSubCategory
     *
     * @ORM\ManyToOne(targetEntity="ListingSubCategory", cascade={"persist"})
     * @ORM\JoinColumn(name="subcategory_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $subcategoryId;


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
     * @return ListingListingSubCategory
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
     * Set subcategoryId
     *
     * @param ListingSubCategory $subcategory
     *
     * @return ListingListingSubCategory
     */
    public function setSubcategoryId($subcategory)
    {
        $this->subcategoryId = $subcategory;

        return $this;
    }

    /**
     * Get subcategoryId
     *
     * @return ListingSubCategory
     */
    public function getSubcategoryId()
    {
        return $this->subcategoryId;
    }
}
