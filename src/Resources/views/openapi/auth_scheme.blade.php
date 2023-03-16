  /auth/login:
    post:
      tags:
        - Authorization
      summary: Receive authorization token
      parameters:
        - in: query
          name: email
          description: Administrator email
          required: false
          schema:
            type: string
            example: {{ $email }}
        - in: query
          name: password
          description: Password from administration
          schema:
            type: string
            example: heslo123
      responses:
        '200':
          description: Success response
          content:
            application/json:
              schema:
                type: object
                properties:
                    data:
                      type: object
                      properties:
                        user:
                          $ref: '#/components/schemas/{{ class_basename(get_class(Admin::getAuthModel())) }}'
                        token:
                          type: object
                          properties:
                            token:
                              type: string
                              example: 6|QthWyWYy5IMwE8eLxdkTJNqdav4D91um060hAvm8