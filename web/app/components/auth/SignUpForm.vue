<script setup lang="ts">
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Field,
  FieldDescription,
  FieldGroup,
  FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";

const { login } = useSanctumAuth();

type SignUpForm = {
  email?: string;
  password?: string;
  password_confirmation?: string;
};
const { data, submit, errors, processing } = useApiForm<SignUpForm>({
  path: "/api/auth/register",
  initialData: {},
});
const onFormSubmit = () =>
  submit("post", () =>
    login({
      email: data.email,
      password: data.password,
    }),
  );
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Create an account</CardTitle>
      <CardDescription>
        Enter your information below to create your account
      </CardDescription>
    </CardHeader>
    <CardContent>
      <form @submit.prevent="onFormSubmit">
        <FieldGroup>
          <Field :data-invalid="!!errors.email">
            <FieldLabel for="email"> Email</FieldLabel>
            <Input
              id="email"
              v-model="data.email"
              :disabled="processing"
              placeholder="m@example.com"
              required
              :aria-invalid="!!errors.email"
            />
            <FieldDescription>
              We'll use this to contact you. We will not share your email with
              anyone else.
            </FieldDescription>
            <FieldError v-if="!!errors.email">{{ errors.email }}</FieldError>
          </Field>
          <Field>
            <FieldLabel for="password"> Password</FieldLabel>
            <Input
              id="password"
              v-model="data.password"
              :disabled="processing"
              type="password"
              required
            />
            <FieldDescription
              >Must be at least 8 characters long.
            </FieldDescription>
            <FieldError v-if="!!errors.password">{{
              errors.password
            }}</FieldError>
          </Field>
          <Field>
            <FieldLabel for="confirm-password"> Confirm Password</FieldLabel>
            <Input
              id="confirm-password"
              v-model="data.password_confirmation"
              :disabled="processing"
              type="password"
              required
            />
            <FieldDescription>Please confirm your password.</FieldDescription>
            <FieldError v-if="!!errors.password_confirmation">{{
              errors.password_confirmation
            }}</FieldError>
          </Field>
          <FieldGroup>
            <Field>
              <Button type="submit" :disabled="processing">
                Create Account</Button
              >
              <!--              <Button variant="outline" type="button">-->
              <!--                Sign up with Google-->
              <!--              </Button>-->
              <FieldDescription class="px-6 text-center">
                Already have an account?
                <NuxtLink href="/login">Sign in</NuxtLink>
              </FieldDescription>
            </Field>
          </FieldGroup>
        </FieldGroup>
      </form>
    </CardContent>
  </Card>
</template>
