<?php

/*
 * This file is part of the Cocorico package.
 *
 * (c) Cocolabs SAS <contact@cocolabs.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cocorico\CoreBundle\Controller\Frontend;

use Cocorico\CoreBundle\Entity\Listing;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Listing controller.
 *
 * @Route("/listing")
 */

class ListingController extends Controller
{
    /**
     * Creates a new Listing entity.
     *
     * @Route("/new", name="cocorico_listing_new")
     * @Method({"GET", "POST"})
     *
     * @param  Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $listingHandler = $this->get('cocorico.form.handler.listing');
        $listing = $listingHandler->init();
        $form = $this->createCreateForm($listing);
        $success = $listingHandler->process($form);

//        if ($success) {
//            $url = $this->generateUrl(
//                'cocorico_dashboard_listing_edit_presentation',
//                array('id' => $listing->getId())
//            );
//
//            $this->container->get('session')->getFlashBag()->add(
//                'success',
//                $this->container->get('translator')->trans('listing.new.success', array(), 'cocorico_listing')
//            );
//            return $this->redirect($url);
//        }

        return $this->render(
            'CocoricoCoreBundle:Frontend/Listing:new.html.twig',
            array(
                'listing' => $listing,
                'form' => $form->createView(),
                )
        );
    }

    /**
     * @author Sarthak Patidar <sarthakpatidar15@gmail.com>
     *
     * Creates a Listing import entity.
     *
     * @Route("/import", name="cocorico_listing_import")
     * @Method({"GET"})
     *
     * @param  Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function import(Request $request){
        $rowNo = array();
        $collection = array();
        $inserted_id = array();
        $rowNo[0] = 0;
        $i = 0;
        // $fp is file pointer to file listing.csv
        if (($fp = fopen("listing.csv", "r")) !== FALSE) {
            while (($row = fgetcsv($fp, 1000, ",")) !== FALSE) {
                $listingHandler = $this->get('cocorico.form.handler.listing');
                $listing = $listingHandler->init();
//                $form = $this->createCreateForm($listing);
                $success = $listingHandler->processImport($listing);
                $id = $listing->getId();
                $num = count($row);
                $rowNo[$i] = $num;
                $i++;
                array_push($collection,$row);
                if(!$success)
                {
                    break;
                }
                else
                {
                    array_push($inserted_id,$id);
                }
            }
            fclose($fp);
        }

        return $this->render(
            'CocoricoCoreBundle:Frontend/Listing:import.html.twig',
            array(
                'collection' => $collection,
                'insert_id' => $inserted_id,
            )
        );
    }

    /**
     * Creates a form to create a Listing entity.
     *
     * @param Listing $listing The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Listing $listing)
    {
        $form = $this->get('form.factory')->createNamed(
            'listing',
            'listing_new',
            $listing,
            array(
                'method' => 'POST',
                'action' => $this->generateUrl('cocorico_listing_new'),
            )
        );
        return $form;
    }

    /**
     * Finds and displays a Listing entity.
     *
     * @Route("/{slug}/show", name="cocorico_listing_show", requirements={
     *      "slug" = "[a-z0-9-]+$"
     * })
     * @Method("GET")
     * @Security("is_granted('view', listing)")
     * @ParamConverter("listing", class="Cocorico\CoreBundle\Entity\Listing", options={"repository_method" = "findOneBySlug"})
     *
     * @param Request $request
     * @param Listing $listing
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(Request $request, Listing $listing)
    {
        $reviews = $this->container->get('cocorico.review.manager')->getListingReviews($listing);

        //Breadcrumbs
        $breadcrumbs = $this->get('cocorico.breadcrumbs_manager');
        $breadcrumbs->addListingShowItems($request, $listing);

        return $this->render(
            'CocoricoCoreBundle:Frontend/Listing:show.html.twig',
            array(
                'listing' => $listing,
                'reviews' => $reviews
            )
        );
    }

}

//listing_categories[listingListingCategories][]=2
//listing_categories[_token]=TAAkL1Jfia82pxjddbA_wqEYia21SSI0zYwArkZbGYs
//listing[translations][en][title]=Dem
//listing[translations][en][description]=Dem
//listing[translations][fr][title]=
//listing[translations][fr][description]=
//listing[image][new][]=
//listing[image][uploaded]=068961a38aa63afae4f937dc5eb5c26d2ed3cf87.jpg
//listing[price]=50
//listing[location][coordinate]={
//   "en":{
//      "postal_code":"452001",
//      "postal_code_short":"452001",
//      "locality":"Indore",
//      "locality_short":"Indore",
//      "political":"India",
//      "political_short":"IN",
//      "administrative_area_level_2":"Indore",
//      "administrative_area_level_2_short":"Indore",
//      "administrative_area_level_1":"Madhya+Pradesh",
//      "administrative_area_level_1_short":"MP",
//      "country":"India",
//      "country_short":"IN"
//   },
//   "formatted_address":"Indore,+Madhya+Pradesh+452001,+India",
//   "types":[
//      "postal_code"
//   ],
//   "location_type":"APPROXIMATE",
//   "viewport":{
//      "south":22.6845079,
//      "west":75.85899970000003,
//      "north":22.7449885,
//      "east":75.91539260000002
//   },
//   "bounds":{
//      "south":22.6845079,
//      "west":75.85899970000003,
//      "north":22.7449885,
//      "east":75.91539260000002
//   },
//   "location":{
//      "lat":22.7081955,
//      "lng":75.88244220000001
//   },
//   "lat":22.7081955,
//   "lng":75.88244220000001,
//   "fr":{
//      "street_number":"58",
//      "street_number_short":"58",
//      "route":"Godhra+Highway",
//      "route_short":"Godhra+Hwy",
//      "neighborhood":"Opposite+CCI+Cricket+Club,+Near+Nehru+Church,+Near+Chota+Nehru+StadiumChota+Nehru+Stadium",
//      "neighborhood_short":"Opposite+CCI+Cricket+Club,+Near+Nehru+Church,+Near+Chota+Nehru+StadiumChota+Nehru+Stadium",
//      "political":"Inde",
//      "political_short":"IN",
//      "sublocality":"Navlakha",
//      "sublocality_short":"Navlakha",
//      "sublocality_level_2":"South+Tukoganj",
//      "sublocality_level_2_short":"South+Tukoganj",
//      "sublocality_level_1":"Navlakha",
//      "sublocality_level_1_short":"Navlakha",
//      "locality":"Indore",
//      "locality_short":"Indore",
//      "administrative_area_level_2":"Indore",
//      "administrative_area_level_2_short":"Indore",
//      "administrative_area_level_1":"Madhya+Pradesh",
//      "administrative_area_level_1_short":"MP",
//      "country":"Inde",
//      "country_short":"IN",
//      "postal_code":"452001",
//      "postal_code_short":"452001"
//   }
//}
//listing[location][country]=IN
//listing[location][city]=Indore
//listing[location][zip]=452001
//listing[location][street_number]=147/C
//listing[location][route]=Greater Palasia
//listing[user][lastName]=
//listing[user][firstName]=
//listing[user][birthday][day]=1
//listing[user][birthday][month]=1
//listing[user][birthday][year]=2000
//listing[user][countryOfResidence]=FR
//listing[user][email]=
//listing[user][plainPassword][first]=
//listing[user][plainPassword][second]=
//listing[user_login][_username]=demo@star.com
//listing[user_login][_password]=kitten
//listing[tac]=1
//listing[_token]=XzIPzHjUX9bUTAdDx4e7pqq8nfvCJtr5xjtHWpjZWgo

//object(Cocorico\CoreBundle\Entity\Listing)#2120 (27)
//{
//["id":"Cocorico\CoreBundle\Entity\Listing":private]=> NULL
//["user":"Cocorico\CoreBundle\Entity\Listing":private]=> NULL
//["location":"Cocorico\CoreBundle\Entity\Listing":private]=> NULL
//["listingListingCategories":"Cocorico\CoreBundle\Entity\Listing":private]=> object(Doctrine\Common\Collections\ArrayCollection)#3394 (1)
//{
//["elements":"Doctrine\Common\Collections\ArrayCollection":private]=> array(0) { }
//    }
//    ["images":"Cocorico\CoreBundle\Entity\Listing":private]=> object(Doctrine\Common\Collections\ArrayCollection)#7112 (1)
//{
//["elements":"Doctrine\Common\Collections\ArrayCollection":private]=> array(0) { }
//    }
//    ["listingListingCharacteristics":"Cocorico\CoreBundle\Entity\Listing":private]=> object(Doctrine\Common\Collections\ArrayCollection)#3344 (1)
//{
//["elements":"Doctrine\Common\Collections\ArrayCollection":private]=> array(0) { }
//    }
//    ["discounts":"Cocorico\CoreBundle\Entity\Listing":private]=> object(Doctrine\Common\Collections\ArrayCollection)#3073 (1)
//{
//["elements":"Doctrine\Common\Collections\ArrayCollection":private]=> array(0) { }
//    }
//    ["bookings":"Cocorico\CoreBundle\Entity\Listing":private]=> object(Doctrine\Common\Collections\ArrayCollection)#7131 (1)
//{
//["elements":"Doctrine\Common\Collections\ArrayCollection":private]=> array(0) { }
//    }
//    ["threads":"Cocorico\CoreBundle\Entity\Listing":private]=> object(Doctrine\Common\Collections\ArrayCollection)#7138 (1)
//{
//["elements":"Doctrine\Common\Collections\ArrayCollection":private]=> array(0) { }
//    }
//    ["options":"Cocorico\CoreBundle\Entity\Listing":private]=> object(Doctrine\Common\Collections\ArrayCollection)#7144 (1)
//{
//["elements":"Doctrine\Common\Collections\ArrayCollection":private]=> array(0) { }
//    }
//    ["status":protected]=> int(1)
//["type":protected]=> NULL
//["price":protected]=> NULL
//["certified":protected]=> NULL
//["minDuration":protected]=> NULL
//["maxDuration":protected]=> NULL
//["cancellationPolicy":protected]=> int(1)
//["averageRating":protected]=> NULL
//["commentCount":protected]=> int(0)
//["adminNotation":protected]=> NULL
//["availabilitiesUpdatedAt":protected]=> NULL
//["createdAt":protected]=> NULL
//["updatedAt":protected]=> NULL
//["translations":protected]=> object(Doctrine\Common\Collections\ArrayCollection)#7251 (1)
//{
//["elements":"Doctrine\Common\Collections\ArrayCollection":private]=> array(0) { }
//    }
//    ["newTranslations":protected]=> NULL
//["currentLocale":protected]=> NULL
//["defaultLocale":protected]=> string(2) "en"
//}
