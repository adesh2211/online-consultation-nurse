<?php
/**
 * @SWG\Swagger(
 *     schemes={"https"},
 *     host=API_HOST,
 *     basePath="/",
 *     @SWG\Info(
 *         version="1.0.0",
 *         title="Consultant APP Swagger",
 *         description="Integrate Swagger in Laravel application",
 *         termsOfService="",
 *         @SWG\Contact(
 *             email="adesh@codebrewinnovations.com"
 *         ),
 *     ),
 *   @SWG\SecurityScheme(
 *   securityDefinition="Bearer",
 *   type="apiKey",
 *   name="Authorization",
     in="header",
 * ),
 *   @SWG\SecurityScheme(
 *   securityDefinition="APP ID",
 *   type="apiKey",
 *   name="app-id",
     in="header",
 * ),
 * )
 */
