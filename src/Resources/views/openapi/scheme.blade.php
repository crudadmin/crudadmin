  /models_scheme:
    get:
      tags:
        - Base
      summary: GET OPENAPI scheme for given model. Only for generating swagger scheme.
      security:
        - bearerAuth: []
      parameters:
        - in: query
          name: models
          schema:
            type: string
          required: false
          description: List of models which you want display
          example: products,orders,...
      responses:
        '200':
          description: Success response

