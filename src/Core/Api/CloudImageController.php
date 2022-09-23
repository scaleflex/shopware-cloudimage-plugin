<?php declare(strict_types=1);

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;

/**
 * @RouteScope(scopes={"api"})
 */
class CloudImageController extends AbstractController
{
    /**
     * @var EntityRepositoryInterface
     */
    private $cloudImageRepository;
}