<?php

namespace Tests\App\Helper;

use App\Helper\UserGroupHelper;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\User\User as APIUser;
use eZ\Publish\Core\MVC\Symfony\Security\User;
use eZ\Publish\Core\Repository\Repository;
use eZ\Publish\Core\Repository\Values\Content\Content;
use eZ\Publish\Core\Repository\Values\Content\VersionInfo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class UserGroupsTest extends TestCase
{
    public function testIsCurrentUserInOneOfTheGroupsWithoutLoggedUser()
    {
        $repository = $this->createMock(Repository::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn(false);

        $userGroupsLocationIds = [36];

        $subject = new UserGroupHelper(
            $repository,
            $tokenStorage
        );

        $result = $subject->isCurrentUserInOneOfTheGroups($userGroupsLocationIds);

        $this->assertFalse($result);
    }

    public function testIsCurrentUserInOneOfTheGroupsWithLoggedUser()
    {
        $repository = $this->createMock(Repository::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $token = new UsernamePasswordToken('foo', 'bar', 'provider');
        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $userGroupsLocationIds = [36];

        $subject = new UserGroupHelper(
            $repository,
            $tokenStorage
        );

        $result = $subject->isCurrentUserInOneOfTheGroups($userGroupsLocationIds);

        $this->assertFalse($result);
    }

    public function testIsCurrentUserInOneOfTheGroupsWithLoggedEzUser()
    {
        $repository = $this->createMock(Repository::class);

        $groups = [
            new \eZ\Publish\Core\Repository\Values\User\UserGroup([
                'content' => new Content([
                    'versionInfo' => new VersionInfo([
                        'contentInfo' => new ContentInfo([
                            'id' => 1,
                            'mainLocationId' => 12,
                        ]),
                    ]),
                    'internalFields' => [],
                ]),
            ]),
            new \eZ\Publish\Core\Repository\Values\User\UserGroup([
                'content' => new Content([
                    'versionInfo' => new VersionInfo([
                        'contentInfo' => new ContentInfo([
                            'id' => 2,
                            'mainLocationId' => 15,
                        ]),
                    ]),
                    'internalFields' => [],
                ]),
            ]),
        ];

        $repository
            ->expects($this->once())
            ->method('sudo')
            ->willReturn($groups);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $apiUser = $this->createMock(APIUser::class);
        $tokenUser = new User($apiUser);
        $token = new UsernamePasswordToken($tokenUser, 'foo', 'bar');

        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $userGroupsLocationIds = [36, 15];

        $subject = new UserGroupHelper(
            $repository,
            $tokenStorage
        );

        $result = $subject->isCurrentUserInOneOfTheGroups($userGroupsLocationIds);

        $this->assertTrue($result);
    }
}
