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

use Cocorico\CoreBundle\Document\ListingAvailability;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Cocorico\UserBundle\Entity\User;
use Cocorico\UserBundle\Form\Handler\RegistrationFormHandler;
use Cocorico\CoreBundle\Entity\Listing;
use Cocorico\CoreBundle\Entity\ListingLocation;
use Cocorico\CoreBundle\Entity\ListingListingCategory;
use Cocorico\CoreBundle\Entity\ListingSubCategory;
use Cocorico\CoreBundle\Entity\ListingEventType;
use Cocorico\CoreBundle\Entity\ListingListingEventType;
use Cocorico\CoreBundle\Entity\ListingListingSubCategory;

ob_start();
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
//        var_dump($request->request->get('listing'));

        if ($success) {
            $url = $this->generateUrl(
                'cocorico_dashboard_listing_edit_presentation',
                array('id' => $listing->getId())
            );

            $this->container->get('session')->getFlashBag()->add(
                'success',
                $this->container->get('translator')->trans('listing.new.success', array(), 'cocorico_listing')
            );
            return $this->redirect($url);
        }

        return $this->render(
            'CocoricoCoreBundle:Frontend/Listing:new.html.twig',
            array(
                'listing_data' => $success,
                'listing' => $listing,
                'form' => $form->createView(),
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
