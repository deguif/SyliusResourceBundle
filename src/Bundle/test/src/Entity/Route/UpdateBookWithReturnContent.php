<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Entity\Route;

use App\Entity\Book;
use Sylius\Component\Resource\Annotation\SyliusRoute;

#[SyliusRoute(
    name: 'update_book_with_return_content',
    path: '/books/{id}',
    methods: ['GET', 'PUT'],
    controller: 'app.controller.book::updateAction',
    returnContent: true,
)]
final class UpdateBookWithReturnContent extends Book
{
}
