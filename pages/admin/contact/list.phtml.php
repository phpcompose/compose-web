<?php

use Compose\Container\ContainerAwareInterface;
use Compose\Container\ContainerAwareTrait;
use Compose\Web\Module\Contact\Repository\ContactEntryRepositoryInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;

return new class implements ContainerAwareInterface {
    use ContainerAwareTrait;

    public function __invoke(ServerRequestInterface $request, ?string $action = null, int|string|null $id = null): array|RedirectResponse
    {
        /** @var ContactEntryRepositoryInterface $repo */
        $repo = $this->getContainer()->get(ContactEntryRepositoryInterface::class);

        if ($action !== null && $id !== null) {
            $entryId = (int) $id;
            switch ($action) {
                case 'read':
                    $repo->setRead($entryId, true);
                    break;
                case 'unread':
                    $repo->setRead($entryId, false);
                    break;
                case 'star':
                    $repo->setStarred($entryId, true);
                    break;
                case 'unstar':
                    $repo->setStarred($entryId, false);
                    break;
                default:
                    // noop
                    break;
            }

            $redirect = $request->getHeaderLine('referer') ?: '/admin/contact/list';
            return new RedirectResponse($redirect);
        }

        return [
            'title' => 'Contact Entries',
            'entries' => $repo->fetchRecent(100),
        ];
    }
};
