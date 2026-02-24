<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('auth')) {

            $isAjax = strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';

            if ($isAjax) {
                return service('response')->setStatusCode(401)->setJSON([
                    'ok'  => false,
                    'msg' => 'No autenticado',
                ]);
            }

            return redirect()->to(site_url('/'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nada
    }
}