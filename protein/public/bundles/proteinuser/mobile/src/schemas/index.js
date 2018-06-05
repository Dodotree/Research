import { schema, normalize } from 'normalizr'

const countrySchema = new schema.Entity('countries',
  { owner: athleteSchema },
  {
    idAttribute: country => country.id
  })

// Entity(key, definition = {}, options = {})

const medalSchema = new schema.Entity('medals',
  { },
  {
    idAttribute: medal => medal.id
  })

const athleteSchema = new schema.Entity('athletes',
  {
    'country': countrySchema,
    'medals': [medalSchema]
  },
  {
    idAttribute: athlete => athlete.id
  })

const userSchema = new schema.Entity('users')

const loggedUserSchema = new schema.Entity('loggedUsers',
  {
    'user': userSchema
  },
  {
    idAttribute: loggedUser => loggedUser.user.id
  })

export const Schemas = {
  LOGGED_USER: loggedUserSchema,
  USER: userSchema,
  USER_ARRAY: [userSchema],
  ATHLETE: athleteSchema,
  ATHLETE_ARRAY: [athleteSchema]
}

const normalizedData = normalize({user:{'id': 2, 'username': 'boi'}}, loggedUserSchema)

console.log('MIDDLEWARE INIT SCHEMAS', normalizedData)
